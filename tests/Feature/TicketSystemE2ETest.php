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
}
