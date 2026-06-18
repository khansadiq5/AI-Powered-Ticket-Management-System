<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\TicketReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDetailsAndReplyThreadTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $agent1;
    protected $agent2;
    protected $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->agent1 = User::create([
            'name' => 'Agent One',
            'email' => 'agent1@test.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $this->agent2 = User::create([
            'name' => 'Agent Two',
            'email' => 'agent2@test.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $this->ticket = Ticket::create([
            'subject' => 'Issue with login',
            'body' => 'I cannot log in to my account.',
            'sender_name' => 'John Student',
            'sender_email' => 'john@student.edu',
            'status' => 'open',
            'priority' => 'low',
            'assigned_to' => $this->agent1->id,
            'message_id' => 'msg-12345',
        ]);
    }

    /**
     * Test guest user access restrictions to ticket details and replies.
     */
    public function test_guest_user_cannot_view_details_or_reply(): void
    {
        $this->get("/agent/tickets/{$this->ticket->id}")
            ->assertRedirect('/login');

        $this->post("/agent/tickets/{$this->ticket->id}/replies", ['body' => 'Test reply'])
            ->assertRedirect('/login');
    }

    /**
     * Test authorization rules for viewing ticket details.
     */
    public function test_ticket_details_authorization_rules(): void
    {
        // 1. Authorized Agent can view the ticket details
        $this->actingAs($this->agent1)
            ->get("/agent/tickets/{$this->ticket->id}")
            ->assertStatus(200)
            ->assertSee($this->ticket->subject)
            ->assertSee($this->ticket->body)
            ->assertSee('Post a Reply');

        // 2. Admin can view the ticket details
        $this->actingAs($this->admin)
            ->get("/admin/tickets/{$this->ticket->id}")
            ->assertStatus(200)
            ->assertSee($this->ticket->subject)
            ->assertSee('Assign Agent');

        // 3. Unauthorized Agent (not assigned) is blocked (403)
        $this->actingAs($this->agent2)
            ->get("/agent/tickets/{$this->ticket->id}")
            ->assertStatus(403);
    }

    /**
     * Test posting a reply via standard HTML form submission.
     */
    public function test_post_reply_via_standard_submission(): void
    {
        // Agent 1 replies
        $response = $this->actingAs($this->agent1)
            ->post("/agent/tickets/{$this->ticket->id}/replies", [
                'body' => 'Working on this issue right now.',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent1->id,
            'body' => 'Working on this issue right now.',
        ]);

        // Verify reply is rendered on the details page
        $this->actingAs($this->agent1)
            ->get("/agent/tickets/{$this->ticket->id}")
            ->assertSee('Working on this issue right now.')
            ->assertSee('Agent One');
    }

    /**
     * Test posting a reply via AJAX returns a JSON response.
     */
    public function test_post_reply_via_ajax_submission(): void
    {
        $response = $this->actingAs($this->agent1)
            ->postJson("/agent/tickets/{$this->ticket->id}/replies", [
                'body' => 'AJAX reply message.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reply posted successfully.',
                'reply' => [
                    'body' => 'AJAX reply message.',
                    'user' => [
                        'name' => 'Agent One',
                        'role' => 'agent',
                        'initial' => 'A',
                    ]
                ]
            ]);

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->agent1->id,
            'body' => 'AJAX reply message.',
        ]);
    }

    /**
     * Test reply submission authorization constraints.
     */
    public function test_post_reply_authorization_rules(): void
    {
        // Unauthorized agent trying to reply gets 403
        $this->actingAs($this->agent2)
            ->post("/agent/tickets/{$this->ticket->id}/replies", [
                'body' => 'I am trying to reply to an unassigned ticket.',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('ticket_replies', [
            'body' => 'I am trying to reply to an unassigned ticket.',
        ]);
    }
}
