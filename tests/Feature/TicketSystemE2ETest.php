<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketClassifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketSystemE2ETest extends TestCase
{
    use RefreshDatabase;

    /**
     * E2E flow testing authentication, user administration, AI ticket classification,
     * ticket assignment, and agent resolution workflow.
     */
    public function test_complete_ticket_system_e2e_flow(): void
    {
        // ---------------------------------------------------------------------
        // 1. GUEST ACCESS RESTRICTIONS
        // ---------------------------------------------------------------------
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/agent')->assertRedirect('/login');
        
        // Clear intended redirect to avoid polluting standard login redirects
        session()->forget('url.intended');

        // Create Admin and Agent roles
        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@helpdesk.com',
            'password' => bcrypt('admin-password'),
            'role' => 'admin',
        ]);

        $agent = User::create([
            'name' => 'Support Agent 1',
            'email' => 'agent1@helpdesk.com',
            'password' => bcrypt('agent-password'),
            'role' => 'agent',
        ]);

        // ---------------------------------------------------------------------
        // 2. ADMIN LOGIN AND USER ADMINISTRATION
        // ---------------------------------------------------------------------
        // Login as Admin
        $response = $this->post('/login', [
            'email' => 'admin@helpdesk.com',
            'password' => 'admin-password',
        ]);
        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        // Visit User Management page
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200);

        // Admin creates a second Agent
        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Support Agent 2',
            'email' => 'agent2@helpdesk.com',
            'password' => 'agent-password-123',
            'role' => 'agent',
        ]);
        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', ['email' => 'agent2@helpdesk.com', 'role' => 'agent']);
        $newAgent = User::where('email', 'agent2@helpdesk.com')->first();

        // Admin updates the new Agent's name
        $response = $this->actingAs($admin)->put("/admin/users/{$newAgent->id}", [
            'user_id' => $newAgent->id,
            'name' => 'Support Agent 2 (Updated)',
            'email' => 'agent2@helpdesk.com',
            'role' => 'agent',
        ]);
        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', ['id' => $newAgent->id, 'name' => 'Support Agent 2 (Updated)']);

        // ---------------------------------------------------------------------
        // 3. TICKET CREATION AND GEMINI AI CLASSIFICATION
        // ---------------------------------------------------------------------
        // Create an incoming support ticket (simulating an email fetch)
        $ticket = Ticket::create([
            'subject' => 'URGENT: Billing charge incorrect',
            'body' => 'Hi support, I was billed twice for my subscription this month. Please resolve.',
            'sender_name' => 'Jane Student',
            'sender_email' => 'jane@student.edu',
            'status' => 'open',
            'priority' => 'medium',
            'message_id' => 'msg-99283-abc',
        ]);

        // Mock the Gemini API call for classification
        config(['services.gemini.api_key' => 'fake-api-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'category' => 'Refund',
                                        'priority' => 'high',
                                        'summary' => 'Student was billed twice for subscription this month and requests resolution.',
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Classify the ticket using the classification service
        $classifier = app(TicketClassifierService::class);
        $classifiedTicket = $classifier->classify($ticket);

        // Assert ticket attributes updated by AI
        $this->assertEquals('Refund', $classifiedTicket->category);
        $this->assertEquals('high', $classifiedTicket->priority);
        $this->assertEquals('Student was billed twice for subscription this month and requests resolution.', $classifiedTicket->ai_summary);

        // ---------------------------------------------------------------------
        // 4. ADMIN TICKET ASSIGNMENT
        // ---------------------------------------------------------------------
        // Admin views all tickets index and tests the new category filter
        $this->actingAs($admin)->get('/admin/tickets?category=Refund')->assertStatus(200);

        // Admin views ticket details and sees the Assign Agent form
        $response = $this->actingAs($admin)->get("/admin/tickets/{$classifiedTicket->id}");
        $response->assertStatus(200);
        $response->assertSee('Assign Agent');

        // Admin assigns the ticket to Support Agent 1
        $response = $this->actingAs($admin)->patch("/admin/tickets/{$classifiedTicket->id}/assign", [
            'assigned_to' => $agent->id,
        ]);
        $this->assertDatabaseHas('tickets', [
            'id' => $classifiedTicket->id,
            'assigned_to' => $agent->id,
        ]);

        // ---------------------------------------------------------------------
        // 5. AGENT WORKFLOW & TICKET RESOLUTION
        // ---------------------------------------------------------------------
        // Logout Admin
        $this->post('/logout');

        // Login as Support Agent 1
        $response = $this->post('/login', [
            'email' => 'agent1@helpdesk.com',
            'password' => 'agent-password',
        ]);
        $response->assertRedirect('/agent');
        $this->assertAuthenticatedAs($agent);

        // Agent visits dashboard
        $this->actingAs($agent)->get('/agent')->assertStatus(200);

        // Agent views ticket details and does NOT see the Assign Agent form
        $response = $this->actingAs($agent)->get("/agent/tickets/{$classifiedTicket->id}");
        $response->assertStatus(200);
        $response->assertDontSee('Assign Agent');
        $response->assertSee('Update Status');

        // Agent updates ticket category to "Technical"
        $response = $this->actingAs($agent)->patch("/agent/tickets/{$classifiedTicket->id}/category", [
            'category' => 'Technical',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $classifiedTicket->id,
            'category' => 'Technical',
        ]);

        // Agent posts a reply
        $response = $this->actingAs($agent)->post("/agent/tickets/{$classifiedTicket->id}/replies", [
            'body' => 'I have processed your technical query.',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $classifiedTicket->id,
            'user_id' => $agent->id,
            'body' => 'I have processed your technical query.',
        ]);

        // Agent updates ticket status to "resolved"
        $response = $this->actingAs($agent)->patch("/agent/tickets/{$classifiedTicket->id}/status", [
            'status' => 'resolved',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $classifiedTicket->id,
            'status' => 'resolved',
        ]);

        // Logout Agent
        $this->post('/logout');
        $this->assertGuest();

        // ---------------------------------------------------------------------
        // 6. ADMIN UPDATES CATEGORY & POSTS REPLY
        // ---------------------------------------------------------------------
        $this->post('/login', [
            'email' => 'admin@helpdesk.com',
            'password' => 'admin-password',
        ]);
        $this->assertAuthenticatedAs($admin);

        // Admin updates category to "Refund"
        $response = $this->actingAs($admin)->patch("/agent/tickets/{$classifiedTicket->id}/category", [
            'category' => 'Refund',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('tickets', [
            'id' => $classifiedTicket->id,
            'category' => 'Refund',
        ]);

        // Admin posts a reply
        $response = $this->actingAs($admin)->post("/agent/tickets/{$classifiedTicket->id}/replies", [
            'body' => 'Approved refund request.',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $classifiedTicket->id,
            'user_id' => $admin->id,
            'body' => 'Approved refund request.',
        ]);

        // Logout Admin
        $this->post('/logout');
        $this->assertGuest();
    }

    /**
     * Test the reply polishing endpoint using Gemini API.
     */
    public function test_polish_reply(): void
    {
        $agent = User::create([
            'name' => 'Support Agent 1',
            'email' => 'agent1@helpdesk.com',
            'password' => bcrypt('agent-password'),
            'role' => 'agent',
        ]);

        $ticket = Ticket::create([
            'subject' => 'Billing inquiry',
            'body' => 'I would like a refund please.',
            'sender_name' => 'Jane Customer',
            'sender_email' => 'jane@customer.com',
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => $agent->id,
        ]);

        // Fake the Gemini API call
        config(['services.gemini.api_key' => 'fake-api-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Dear Jane, we have received your request and will process your refund shortly.'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Request without authentication should redirect/fail
        $response = $this->postJson("/agent/tickets/{$ticket->id}/polish-reply", [
            'body' => 'Sure, I will refund you.',
        ]);
        $response->assertStatus(401);

        // Authenticate agent
        $response = $this->actingAs($agent)->postJson("/agent/tickets/{$ticket->id}/polish-reply", [
            'body' => 'Sure, I will refund you.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'polished' => 'Dear Jane, we have received your request and will process your refund shortly.',
        ]);
    }

    /**
     * Test the ticket summarization endpoint using Gemini API.
     */
    public function test_summarize_ticket(): void
    {
        $agent = User::create([
            'name' => 'Support Agent 1',
            'email' => 'agent1@helpdesk.com',
            'password' => bcrypt('agent-password'),
            'role' => 'agent',
        ]);

        $ticket = Ticket::create([
            'subject' => 'Billing inquiry',
            'body' => 'I would like a refund please.',
            'sender_name' => 'Jane Customer',
            'sender_email' => 'jane@customer.com',
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => $agent->id,
        ]);

        // Fake the Gemini API call for summarization
        config(['services.gemini.api_key' => 'fake-api-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'The customer Jane is requesting a refund for a billing inquiry. The agent is assigned to handle it.'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Request without authentication should fail
        $response = $this->postJson("/tickets/{$ticket->id}/summarize");
        $response->assertStatus(401);

        // Authenticate agent
        $response = $this->actingAs($agent)->postJson("/tickets/{$ticket->id}/summarize");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'summary' => 'The customer Jane is requesting a refund for a billing inquiry. The agent is assigned to handle it.',
        ]);

        // Verify the database has been updated
        $this->assertEquals(
            'The customer Jane is requesting a refund for a billing inquiry. The agent is assigned to handle it.',
            $ticket->fresh()->ai_summary
        );
    }

    /**
     * Test the ticket classification job is queued.
     */
    public function test_classify_ticket_job_is_queued(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $ticket = Ticket::create([
            'subject' => 'Help with login',
            'body' => 'I cannot sign in to my account. Please reset my password.',
            'sender_name' => 'Support User',
            'sender_email' => 'user@example.com',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        \App\Jobs\ClassifyTicketJob::dispatch($ticket);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ClassifyTicketJob::class);
    }

    /**
     * Test a ticket is auto-resolved using the knowledge base.
     */
    public function test_auto_resolve_ticket_using_knowledge_base(): void
    {
        $ticket = Ticket::create([
            'subject' => 'How to reset account password?',
            'body' => 'I forgot my password and cannot sign in.',
            'sender_name' => 'John Client',
            'sender_email' => 'john.client@example.com',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        config(['services.gemini.api_key' => 'fake-api-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'category' => 'Technical',
                                            'priority' => 'high',
                                            'summary' => 'Forgot password assistance',
                                        ])
                                    ]
                                ]
                            ]
                        ]
                    ]
                ], 200)
                ->push([
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    [
                                        'text' => json_encode([
                                            'resolved' => true,
                                            'reply' => 'To reset your password, please go to the login page and click Forgot Password.',
                                        ])
                                    ]
                                ]
                            ]
                        ]
                    ]
                ], 200)
        ]);

        $job = new \App\Jobs\ClassifyTicketJob($ticket);
        app()->call([$job, 'handle']);

        $ticket->refresh();
        $this->assertEquals('resolved', $ticket->status);

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'body' => 'To reset your password, please go to the login page and click Forgot Password.',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ai.assistant@helpdesk.com',
            'name' => 'AI',
        ]);
    }

    /**
     * Test that new and processing tickets are excluded from admin and agent ticket lists.
     */
    public function test_new_and_processing_tickets_are_hidden_from_lists(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'admin-test@helpdesk.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $agent = User::create([
            'name' => 'Support Agent Test',
            'email' => 'agent-test@helpdesk.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $regularTicket = Ticket::create([
            'subject' => 'Regular query',
            'body' => 'I have a question.',
            'sender_name' => 'John Client',
            'sender_email' => 'john.client@example.com',
            'status' => 'open',
            'priority' => 'medium',
            'assigned_to' => $agent->id,
        ]);

        $processingTicket = Ticket::create([
            'subject' => 'AI processing query',
            'body' => 'I forgot my password.',
            'sender_name' => 'Jane Client',
            'sender_email' => 'jane.client@example.com',
            'status' => 'processing',
            'priority' => 'low',
            'assigned_to' => $agent->id,
        ]);

        $newTicket = Ticket::create([
            'subject' => 'Brand new query',
            'body' => 'Refund me please.',
            'sender_name' => 'Bob Client',
            'sender_email' => 'bob.client@example.com',
            'status' => 'new',
            'priority' => 'high',
            'assigned_to' => $agent->id,
        ]);

        // 1. Visit as admin
        $response = $this->actingAs($admin)->get('/admin/tickets');
        $response->assertStatus(200);
        $response->assertSee($regularTicket->subject);
        $response->assertDontSee($processingTicket->subject);
        $response->assertDontSee($newTicket->subject);

        // 2. Visit as agent
        $response = $this->actingAs($agent)->get('/agent');
        $response->assertStatus(200);
        $response->assertSee($regularTicket->subject);
        $response->assertDontSee($processingTicket->subject);
        $response->assertDontSee($newTicket->subject);
    }

    /**
     * Test that the Admin Dashboard correctly calculates and displays AI & SLA metrics.
     */
    public function test_admin_dashboard_shows_ai_and_sla_metrics(): void
    {
        $admin = User::create([
            'name' => 'Metrics Admin',
            'email' => 'metrics-admin@helpdesk.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $aiUser = User::create([
            'name' => 'AI',
            'email' => 'ai.assistant@helpdesk.com',
            'password' => bcrypt('password-ai'),
            'role' => 'agent',
        ]);

        $humanAgent = User::create([
            'name' => 'Human Agent',
            'email' => 'human@helpdesk.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        // 1. Create an open ticket
        Ticket::create([
            'subject' => 'Open query',
            'body' => 'I have a query.',
            'sender_email' => 'sender1@example.com',
            'status' => 'open',
            'priority' => 'low',
        ]);

        // 2. Create a ticket resolved by AI (2 hours resolution time)
        $aiResolvedTicket = new Ticket([
            'subject' => 'AI solved query',
            'body' => 'Password reset.',
            'sender_email' => 'sender2@example.com',
            'status' => 'resolved',
            'priority' => 'low',
        ]);
        $aiResolvedTicket->timestamps = false;
        $aiResolvedTicket->created_at = now()->subHours(2);
        $aiResolvedTicket->updated_at = now();
        $aiResolvedTicket->save();

        $aiResolvedTicket->replies()->create([
            'user_id' => $aiUser->id,
            'body' => 'Use the forgot password page.',
        ]);

        // 3. Create a ticket resolved by Human Agent (4 hours resolution time)
        $humanResolvedTicket = new Ticket([
            'subject' => 'Human solved query',
            'body' => 'Refund request.',
            'sender_email' => 'sender3@example.com',
            'status' => 'resolved',
            'priority' => 'low',
        ]);
        $humanResolvedTicket->timestamps = false;
        $humanResolvedTicket->created_at = now()->subHours(4);
        $humanResolvedTicket->updated_at = now();
        $humanResolvedTicket->save();

        $humanResolvedTicket->replies()->create([
            'user_id' => $humanAgent->id,
            'body' => 'Here is your refund.',
        ]);

        // Visit Admin Dashboard
        $response = $this->actingAs($admin)->get('/admin');
        $response->assertStatus(200);

        // Assert stats in view
        $response->assertSee('Resolved by AI');
        $response->assertSee('AI Success Rate');
        $response->assertSee('Avg Resolution Time');
        
        // Assert the exact numbers
        $response->assertSee('50%');
        $response->assertSee('180 mins');
    }

    /**
     * Test the AI Agent assignment lifecycle (assignment on arrival, unassignment if unresolved).
     */
    public function test_ticket_assignment_lifecycle_to_ai_agent(): void
    {
        // 1. Create a ticket that WILL auto-resolve
        $resolvableTicket = Ticket::create([
            'subject' => 'How to reset account password?',
            'body' => 'I forgot my password and cannot sign in.',
            'sender_name' => 'John Client',
            'sender_email' => 'john.client@example.com',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        // Verify that upon arrival it is assigned to the AI agent
        $aiAgent = User::where('email', 'ai.assistant@helpdesk.com')->first();
        $this->assertNotNull($aiAgent);
        $this->assertEquals($aiAgent->id, $resolvableTicket->assigned_to);

        // Mock success response
        config(['services.gemini.api_key' => 'fake-api-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'category' => 'Technical',
                                'priority' => 'high',
                                'summary' => 'Forgot password assistance',
                            ])
                        ]]]
                    ]]
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'resolved' => true,
                                'reply' => 'Use the reset password form.',
                            ])
                        ]]]
                    ]]
                ], 200)
        ]);

        $job = new \App\Jobs\ClassifyTicketJob($resolvableTicket);
        app()->call([$job, 'handle']);

        $resolvableTicket->refresh();
        $this->assertEquals('resolved', $resolvableTicket->status);
        // It stays assigned to the AI agent
        $this->assertEquals($aiAgent->id, $resolvableTicket->assigned_to);

        // 2. Create a ticket that WILL NOT auto-resolve
        $unresolvableTicket = Ticket::create([
            'subject' => 'Gibberish text here',
            'body' => 'aksjdhaksjdhaksjdh',
            'sender_name' => 'Jane Client',
            'sender_email' => 'jane.client@example.com',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        // Verify that upon arrival it is also assigned to the AI agent
        $this->assertEquals($aiAgent->id, $unresolvableTicket->assigned_to);

        // Mock failure response
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'category' => 'Gibberish',
                                'priority' => 'low',
                                'summary' => 'gibberish query',
                            ])
                        ]]]
                    ]]
                ], 200)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode([
                                'resolved' => false,
                                'reply' => '',
                            ])
                        ]]]
                    ]]
                ], 200)
        ]);

        $job2 = new \App\Jobs\ClassifyTicketJob($unresolvableTicket);
        app()->call([$job2, 'handle']);

        $unresolvableTicket->refresh();
        $this->assertEquals('open', $unresolvableTicket->status);
        // It should be unassigned from the AI agent
        $this->assertNull($unresolvableTicket->assigned_to);
    }

    /**
     * Test the entire Email-to-Ticket and Ticket-to-Email integration flow,
     * including email fetching, duplicate checking, threading, and agent replies.
     */
    public function test_email_to_ticket_and_ticket_to_email_flow(): void
    {
        // 1. Mock Mail fake
        \Illuminate\Support\Facades\Mail::fake();

        config([
            'services.imap.username' => 'test-imap-user',
            'services.imap.password' => 'test-imap-pass',
        ]);

        // 2. Setup mock data
        $senderEmail = 'customer@example.com';
        $senderName = 'Jane Customer';
        $subject = 'Inquiry about pricing';
        $body = 'Hi, what are your subscription tiers?';
        $msgId = 'msg-12345-id@domain.com';

        // 3. Mock Webklex ClientManager, Client, Folder, and Message
        $mockClientManager = \Mockery::mock(\Webklex\PHPIMAP\ClientManager::class);
        $mockClient = \Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $mockFolder = \Mockery::mock(\Webklex\PHPIMAP\Folder::class);
        $mockMessage = \Mockery::mock(\Webklex\PHPIMAP\Message::class);

        $mockClientManager->shouldReceive('make')->andReturn($mockClient);
        $mockClient->shouldReceive('connect')->once()->andReturn($mockClient);
        $mockClient->shouldReceive('getFolder')->with('INBOX')->andReturn($mockFolder);

        $mockQuery = \Mockery::mock(\Webklex\PHPIMAP\Query\WhereQuery::class);
        $mockFolder->shouldReceive('messages')->andReturn($mockQuery);
        $mockQuery->shouldReceive('unseen')->andReturn($mockQuery);
        $mockQuery->shouldReceive('limit')->andReturn($mockQuery);
        $mockQuery->shouldReceive('get')->andReturn(new \Webklex\PHPIMAP\Support\MessageCollection([$mockMessage]));

        $mockMessage->shouldReceive('getMessageId')->andReturn($msgId);
        $mockMessage->shouldReceive('get')->with('in_reply_to')->andReturn(null);
        $mockMessage->shouldReceive('get')->with('references')->andReturn(null);
        $mockMessage->shouldReceive('getHeaders')->andReturn(null);
        $mockMessage->shouldReceive('getInReplyTo')->andReturn(null);
        $mockMessage->shouldReceive('getReferences')->andReturn(null);
        
        $fromObj = (object)[
            'mail' => $senderEmail,
            'personal' => $senderName,
        ];
        $mockMessage->shouldReceive('getFrom')->andReturn([$fromObj]);
        $mockMessage->shouldReceive('getSubject')->andReturn($subject);
        $mockMessage->shouldReceive('getTextBody')->andReturn($body);
        $mockMessage->shouldReceive('getHTMLBody')->andReturn(null);
        $mockMessage->shouldReceive('setFlag')->with('Seen')->once()->andReturn(true);

        // Bind ClientManager in the app container so FetchEmailsCommand uses the mock
        $this->app->instance(\Webklex\PHPIMAP\ClientManager::class, $mockClientManager);

        // 4. Run the FetchEmailsCommand
        $this->artisan('tickets:fetch-emails')
            ->assertExitCode(0);

        // 5. Assert ticket created successfully in the DB
        $this->assertDatabaseHas('tickets', [
            'subject' => $subject,
            'sender_email' => $senderEmail,
            'sender_name' => $senderName,
            'source' => 'email',
            'status' => 'open',
            'message_id' => $msgId,
        ]);

        $ticket = Ticket::where('sender_email', $senderEmail)->first();
        $this->assertNotNull($ticket);

        // 6. Test Threading / Customer Replying to Ticket
        // Create another ClientManager mock to fetch the reply email
        $replyMsgId = 'msg-reply-54321-id@domain.com';
        $replyBody = 'Thank you, but I need more details.';
        
        $mockClientManagerReply = \Mockery::mock(\Webklex\PHPIMAP\ClientManager::class);
        $mockClientReply = \Mockery::mock(\Webklex\PHPIMAP\Client::class);
        $mockFolderReply = \Mockery::mock(\Webklex\PHPIMAP\Folder::class);
        $mockMessageReply = \Mockery::mock(\Webklex\PHPIMAP\Message::class);

        $mockClientManagerReply->shouldReceive('make')->andReturn($mockClientReply);
        $mockClientReply->shouldReceive('connect')->once()->andReturn($mockClientReply);
        $mockClientReply->shouldReceive('getFolder')->with('INBOX')->andReturn($mockFolderReply);

        $mockQueryReply = \Mockery::mock(\Webklex\PHPIMAP\Query\WhereQuery::class);
        $mockFolderReply->shouldReceive('messages')->andReturn($mockQueryReply);
        $mockQueryReply->shouldReceive('unseen')->andReturn($mockQueryReply);
        $mockQueryReply->shouldReceive('limit')->andReturn($mockQueryReply);
        $mockQueryReply->shouldReceive('get')->andReturn(new \Webklex\PHPIMAP\Support\MessageCollection([$mockMessageReply]));

        $mockMessageReply->shouldReceive('getMessageId')->andReturn($replyMsgId);
        $mockMessageReply->shouldReceive('getFrom')->andReturn([$fromObj]);
        $mockMessageReply->shouldReceive('getSubject')->andReturn('Re: ' . $subject);
        $mockMessageReply->shouldReceive('getTextBody')->andReturn($replyBody);
        $mockMessageReply->shouldReceive('getHTMLBody')->andReturn(null);
        $mockMessageReply->shouldReceive('setFlag')->with('Seen')->once()->andReturn(true);

        // Mock headers for threading
        $mockMessageReply->shouldReceive('get')->with('in_reply_to')->andReturn('<' . $msgId . '>');
        $mockMessageReply->shouldReceive('get')->with('references')->andReturn('<' . $msgId . '>');
        $mockMessageReply->shouldReceive('getHeaders')->andReturn(null);
        $mockMessageReply->shouldReceive('getInReplyTo')->andReturn(null);
        $mockMessageReply->shouldReceive('getReferences')->andReturn(null);

        $this->app->instance(\Webklex\PHPIMAP\ClientManager::class, $mockClientManagerReply);

        // Run the FetchEmailsCommand again
        $this->artisan('tickets:fetch-emails')
            ->assertExitCode(0);

        // Assert customer user created/retrieved
        $customerUser = User::where('email', $senderEmail)->first();
        $this->assertNotNull($customerUser);
        $this->assertEquals('customer', $customerUser->role);

        // Assert reply attached to the original ticket
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $customerUser->id,
            'body' => $replyBody,
        ]);

        // 7. Test Outgoing Agent Reply via SMTP
        $agent = User::create([
            'name' => 'Support Agent X',
            'email' => 'agentx@helpdesk.com',
            'role' => 'agent',
            'password' => bcrypt('password'),
        ]);

        $ticket->update([
            'assigned_to' => $agent->id,
            'status' => 'in_progress',
        ]);

        $agentReplyBody = 'Here are our pricing details: $10/mo, $20/mo.';
        
        $response = $this->actingAs($agent)->post("/agent/tickets/{$ticket->id}/replies", [
            'body' => $agentReplyBody,
        ]);
        $response->assertRedirect();

        // Assert the reply was recorded in ticket replies
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => $agentReplyBody,
        ]);

        // Assert the sent mail logged in email logs (to prevent duplicate handling)
        $this->assertDatabaseHas('email_logs', [
            'ticket_id' => $ticket->id,
            'from' => env('SUPPORT_EMAIL', 'support@helpdesk.com'),
            'status' => 'processed',
        ]);
    }
}
