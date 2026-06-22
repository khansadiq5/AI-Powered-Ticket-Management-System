# Helpdesk Ticket System: Email Integration & Architecture Report

This report outlines the architecture and setup of the inbound and outbound email ticketing pipeline for the Laravel Helpdesk Ticket System. It explains how standard client email communication is seamlessly integrated with the automated backend Laravel ticket dashboard.

---

## 1. Project Core Idea

The core purpose of the helpdesk system is to bridge the gap between standard customer emails and a centralized Laravel ticket management dashboard. Rather than requiring clients to log into an external customer portal, they can open support requests and hold complete conversations directly from their personal email client (e.g., Gmail, Outlook).

The application acts as the mediator: receiving incoming messages via webhooks, processing them asynchronously, running AI-based automated routing and classification, displaying them to agents, and routing replies back to the client's inbox.

```
+---------------+              +--------------------+              +--------------------+
|  Client Mail  |  =========>  |  Inbound Gateway   |  =========>  |  Laravel Webhook   |
| (Gmail/Other) |  (Inbound)   | (Postmark Inbound) |   (JSON)     | (/api/webhooks...) |
+---------------+              +--------------------+              +--------------------+
                                                                              │
                                                                              ▼
+---------------+              +--------------------+              +--------------------+
| Client Inbox  |  <=========  | Outbound SMTP / API|  <=========  | Laravel Background |
| (Threaded Mail)  (Headers)   | (Postmark Outbound)|   (Queue)    |   Queue & Job      |
+---------------+              +--------------------+              +--------------------+
```

### The Lifecycle of an Email Ticket
1. **Initiation**: A customer sends an email to the support mailbox.
2. **Inbound Processing**: The email is received by Postmark's inbound stream, which parses the message headers, body, and attachments, converting them into a structured JSON payload.
3. **Webhook Dispatch**: Postmark POSTs the JSON payload to the Laravel webhook endpoint.
4. **Queue & Job processing**: The Laravel controller validates the incoming token and dispatches a background job. The job strips historic email trails, detects conversation threading, creates or links a ticket, runs optional AI classifications, and posts the updates.
5. **Agent Intervention**: An agent views the ticket and replies from the Laravel dashboard.
6. **Outbound Routing**: The system queues the reply. Laravel constructs an email with specific headers matching the original message's thread context and sends it via the Postmark SMTP/API transport.
7. **Thread Collapsing**: The client receives the reply directly inside their existing email thread.

---

## 2. Step-by-Step Email Setup (From Scratch)

Integrating Postmark with your local Laravel application involves setting up mail streams, local tunneling, and framework configurations.

### Postmark Onboarding & Domain Verification
Postmark acts as the mail delivery infrastructure. Follow these steps to onboard and set up your streams:

1. **Sign Up for Postmark**: Create an account on [Postmark](https://postmarkapp.com).
2. **Create a Server**: In the Postmark console, click **Create Server**, name it (e.g., "Helpdesk Production"), and select its environment type.
3. **Set Up an Inbound Stream**:
   - Inside your new server, navigate to the **Streams** tab and click **Create Inbound Stream**.
   - Postmark will generate a unique cryptographic inbound address (e.g., `823f29a85a7d6de8df851a12b49d4795@inbound.postmarkapp.com`).
   - Any email sent or forwarded to this address is processed by Postmark and converted into a webhook payload.
4. **Set Up Sender Signature (Outbound)**:
   - To send emails *from* your application, you must verify the domain or email address you plan to use as the sender.
   - Go to **Sender Signatures** in Postmark and add a new signature (e.g., `2203051050509@paruluniversity.ac.in`).
   - Complete the validation by clicking the verification link sent to that email, and configure the suggested DNS records (DKIM, SPF, and DMARC) for full domain approval.

> [!IMPORTANT]
> **Why regular Gmail accounts are not allowed for outbound sending:**
> Postmark enforces strict delivery standards. Sending outbound emails using a public domain like `@gmail.com` from a third-party server (like Postmark) will trigger SPF, DKIM, and DMARC failures. Public domains explicitly publish policies stating that only Google's own servers can send mail for `@gmail.com`. Attempting to spoof these addresses results in emails being marked as spam or blocked entirely. Therefore, Postmark requires a custom, verified professional domain or signature where you have administrative control over the DNS settings.

---

### Local Bridge (ngrok)
During local development, your Laravel server runs on a local port (e.g., `http://127.0.0.1:8000`). Because this address is private, Postmark's servers in the cloud cannot reach it to deliver webhook payloads. A tunnel is required to bridge the cloud to localhost.

```
+--------------------+            +-------------------+            +---------------------+
|  Postmark Cloud    |  =======>  |   ngrok Tunnel    |  =======>  |  Localhost:8000     |
| (Webhooks Endpoint)|            | (Public HTTPS URL)|            | (Laravel Dev Server)|
+--------------------+            +-------------------+            +---------------------+
```

1. **Install and Run ngrok**:
   Open a terminal and run the following command to expose your local web server:
   ```bash
   ngrok http 8000
   ```
2. **Retrieve the Public URL**:
   ngrok will generate a public address, such as:
   `https://a1b2-34-56-78-90.ngrok-free.app`
3. **Configure the Inbound Webhook**:
   - Go to your Postmark Server dashboard.
   - Select your **Inbound Stream** and click **Settings**.
   - Paste your public ngrok URL into the **Webhook URL** field, appending the webhook route and your shared secret token:
     `https://a1b2-34-56-78-90.ngrok-free.app/api/webhooks/inbound-email/YOUR_SECRET_TOKEN`
   - Save the webhook settings.

---

### Laravel Configuration
Configure the `.env` file in the root of your Laravel project to connect to Postmark.

Add or update the following environment variables:

```env
# Mail Configuration
MAIL_MAILER=postmark
MAIL_FROM_ADDRESS="2203051050509@paruluniversity.ac.in"
MAIL_FROM_NAME="Helpdesk Support"

# Postmark Specific Credentials
POSTMARK_API_KEY="your-postmark-server-api-token"
POSTMARK_INBOUND_ADDRESS="823f29a85a7d6de8df851a12b49d4795@inbound.postmarkapp.com"
POSTMARK_WEBHOOK_TOKEN="YOUR_SECRET_TOKEN"

# Application URL (Matches your local bridge URL during dev)
APP_URL="https://a1b2-34-56-78-90.ngrok-free.app"
```

*Note: In `config/services.php`, these environment values are mapped to configuration keys:*
```php
'postmark' => [
    'key' => env('POSTMARK_API_KEY'),
    'inbound_address' => env('POSTMARK_INBOUND_ADDRESS'),
    'webhook_token' => env('POSTMARK_WEBHOOK_TOKEN'),
],
```

---

## 3. System Architecture & Workflows

### Inbound Workflow (User Mail ➡️ Laravel Database)
The system processes inbound emails asynchronously using Laravel's queue system to prevent blocking connections.

```
[Customer Email]
       │
       ▼
(Postmark Cloud parses email & POSTs JSON)
       │
       ▼
[PostmarkWebhookController @ handleInboundEmail]
       │
       ├──► 1. Verify shared token vs POSTMARK_WEBHOOK_TOKEN (hash_equals)
       ├──► 2. Extract sender info, headers (Message-ID, In-Reply-To, References)
       └──► 3. Dispatch ProcessInboundEmailJob
       │
       ▼ (Returns 200 OK instantly to Postmark)
       
[ProcessInboundEmailJob @ handle] (Queue worker parses asynchronously)
       │
       ├──► 1. Check duplicate message_id in EmailLog
       ├──► 2. Run regex parsing to strip trail/signatures (parseCleanEmailBody)
       ├──► 3. Thread matching:
       │      ├── Check In-Reply-To / References matching logged message IDs
       │      └── Check subject for TKT-XXXXX ticket number format
       │
       ├──► [Match Found?]
       │      ├── Yes ──► Create TicketReply (message_type: incoming)
       │      │           Open ticket if it was resolved/closed
       │      │
       │      └── No ───► Check if Sender Email exists in User DB
       │                    ├── Yes ──► Create Ticket (status: new), run AI assist
       │                    └── No ───► Create Guest Ticket (status: open, user_id: null)
       │                                (No user record is created in DB)
       │
       └──► 4. Log process status in EmailLog
```

#### Key Implementation Details:
* **Token Validation**: Avoids unauthorized calls using `hash_equals()` to compare the URL parameter token directly with the secure local `.env` setting.
* **Database Cleanliness (Guest Support)**: When an unregistered guest sends an email, the system logs the ticket with status `open` and sets `assigned_to` to `null`. Inside the `ticket_replies` table, the `user_id` is set to `null`, and the raw `sender_email` is written directly into the `tickets` table. This prevents the `users` table from filling up with temporary guest rows or spam email accounts, keeping the primary database clean.
* **Regex Email Trail Extraction**: To prevent storing historical email threads (e.g., `"On Fri, 19 Jun 2026... wrote:"`), the helper `parseCleanEmailBody()` slices the body text. It splits the message at the earliest match of predefined regex patterns for Gmail, Outlook, Apple Mail, custom signatures (`-- `), or device signatures (`Sent from my iPhone`). It also removes blockquoted lines (prefixed with `>`) to extract only the fresh content:

```php
protected function parseCleanEmailBody(string $textBody): string
{
    $body = $textBody;
    
    // Split the body at the earliest reply divider line.
    $dividerPatterns = [
        '/^\s*On\s+.{10,80}\s+wrote:\s*$/m',                // Gmail
        '/^\s*-{3,}\s*Original\s+Message\s*-{3,}\s*$/mi',    // Outlook
        '/^_{10,}$/m',                                       // Outlook Horizontal Line
        '/^\s*On\s+.*,\s*.*\s+wrote:\s*$/mi',                // Apple Mail
        '/^\s*From:\s*.*@.*$/mi',                            // Generic mid-body header
        '/^\s*Sent from my\s+/mi',                           // Mobile device signature
        '/^--\s*$/m',                                        // Standard signature delimiter
    ];

    $earliestPos = mb_strlen($body);

    foreach ($dividerPatterns as $pattern) {
        if (preg_match($pattern, $body, $match, PREG_OFFSET_CAPTURE)) {
            $pos = $match[0][1];
            if ($pos < $earliestPos) {
                $earliestPos = $pos;
            }
        }
    }

    if ($earliestPos < mb_strlen($body)) {
        $body = mb_substr($body, 0, $earliestPos);
    }

    // Strip blockquoted lines starting with ">"
    $lines = explode("\n", $body);
    $cleanLines = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*>/', $line)) {
            continue;
        }
        $cleanLines[] = $line;
    }
    
    return trim(implode("\n", $cleanLines));
}
```

---

### Outbound Workflow (Agent Reply ➡️ User Gmail Thread)
When an agent replies, Laravel ensures the email lands in the user's existing email thread rather than creating a separate message in their inbox.

```
[Agent submits reply on Dashboard]
       │
       ▼
[AgentController @ storeReply]
       │
       ├──► 1. Create TicketReply in DB
       ├──► 2. Look up customer's email from ticket sender_email
       └──► 3. Queue AdminReplyMailable
       
[AdminReplyMailable @ Envelope / Headers] (Queue worker processes)
       │
       ├──► Set From: config('mail.from.address') (e.g. 2203051050509@paruluniversity.ac.in)
       ├──► Set ReplyTo: config('services.postmark.inbound_address')
       ├──► Set Subject: "Re: [TKT-XXXXX] " + original subject
       └──► Inject Threading Headers:
              ├── 'In-Reply-To' => '<' + Original Message-ID + '>'
              └── 'References'   => '<' + Original Message-ID + '>'
       │
       ▼ (Sent via Postmark SMTP/API)
       
[Gmail Thread Collapsing Engine]
       │
       ├──► Compares In-Reply-To and References to original inbound Message-ID
       └──► Identifies conversation matches
       │
       ▼
[User Inbox] (Reply appears collapsed inside the original conversation thread)
```

#### How Gmail Thread Collapsing Works:
Every email sent across the internet contains a unique `Message-ID` header. When the customer first emails the support desk, Postmark extracts this ID (e.g., `msg-12345-id@domain.com`) and Laravel saves it in the `tickets` table under the `message_id` column.

When the agent replies from the dashboard, Laravel constructs the `AdminReplyMailable`. In its `headers()` method, it injects two metadata headers:
* **`In-Reply-To`**: Indicates which message this email is responding to.
* **`References`**: Tracks the thread history.

Both headers are populated with the saved `message_id` wrapped in brackets:
```php
public function headers(): Headers
{
    $textHeaders = [];

    if ($this->ticket->message_id) {
        $cleanOriginalId = trim($this->ticket->message_id, " \t\n\r\0\x0B<>");
        $textHeaders['In-Reply-To'] = '<' . $cleanOriginalId . '>';
        $textHeaders['References']  = '<' . $cleanOriginalId . '>';
    }

    return new Headers(text: $textHeaders);
}
```
When Gmail receives this mail, it reads these headers, matches them to the client's sent folder history, and groups the message directly under the existing thread.

---

## 4. Production Deployment & Cloud Scale

When deploying the Laravel Helpdesk application to a production platform like Render.com, several configurations must shift to scale efficiently.

### 1. Removing Local Tunneling
* **Eliminate ngrok**: A local tunnel is no longer required.
* **Environment Configuration**: Set `APP_URL` in the production environment variables to your custom domain or Render subdomain (e.g., `https://support.yourcompany.com` or `https://ticket-system.onrender.com`).
* **Postmark Webhook Endpoint**: Update the inbound stream webhook URL in Postmark's dashboard to point directly to your live production address:
  `https://ticket-system.onrender.com/api/webhooks/inbound-email/YOUR_PRODUCTION_TOKEN`

### 2. High-Performance Queue Configuration
In development, Laravel might run tasks synchronously (`QUEUE_CONNECTION=sync`), making the web server wait while emails are sent. In production, this slows page loads and causes timeouts.

* **Production Queue connection**: Configure a persistent backend queue connection such as `database` or `redis` in `.env`:
  ```env
  QUEUE_CONNECTION=database
  ```
* **Background Worker Service**: On Render, provision a **Background Worker** service alongside your main web service. This service runs the Laravel queue daemon:
  ```bash
  php artisan queue:work --queue=default --tries=3 --delay=3
  ```
  This runs in a continuous loop, handling incoming processing jobs and outbound email tasks in the background without affecting the web dashboard's response times.
* **Process Monitor**: Ensure that Render's worker configuration is kept active. Since database queues in production require running a daemon, setting up a background worker container ensures `queue:work` is kept running persistently.
