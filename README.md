# 🎫 AI Powered Ticket Management System

## 🏁 Project Objective

SmartDesk AI is an event-driven support ecosystem that turns raw email conversations into clean, structured helpdesk tickets. The core goal is to remove the friction of standard support setups: customers don't need to sign up or log into any separate portal—they simply use their everyday email client (like Gmail). 

On the other side, support agents get a unified, role-based web dashboard built in Laravel to manage incoming requests, view organized chat logs, and use Gemini AI to automatically generate contextual drafts and key summaries—all synchronized perfectly back into the user's single email thread.

---

## 📌 The Real-World Problems This Solves

Standard helpdesks frequently run into heavy operational friction points that slow down engineering teams and databases:
- **Portal Fatigue:** Users abandon support channels when forced to create a fresh profile just to ask a quick technical question.
- **Database Thread Bloat:** Standard email forwarders dump the entire history chain (`On Friday, June 19, ... wrote:`) straight into the database text cells on every single reply. This rapidly eats up storage and balloons AI token processing costs.
- **Spam Account Generation:** Automatically generating new user accounts for every single rogue or cold outbound email pollutes the database identity tables over time.
- **Broken Conversation Streams:** Standard dashboard email notifications often trigger brand-new email threads inside the client's inbox, scattering the support context across multiple loose emails.

---

## ⚙️ Core Technical Architecture (How Data Flows)

Here is a quick map of how a single user email flows securely into your local system and turns back into an inline threaded response:

```text
[CUSTOMER EMAIL (GMAIL)]
          │
          ▼ (Sends query: "Cannot access dashboard")
[POSTMARK INBOUND ROUTER] (Captures raw multi-part MIME mail body)
          │
          ▼ (Converts to clean structured JSON packet)
[NGROK ENCRYPTED GATEWAY] (Securely tunnels public cloud data to local network)
          │
          ▼
[LARAVEL WEBHOOK ROUTE] (/api/webhooks/inbound-email)
          │
          ├─► [Instant Handshake]: Returns 200 OK immediately to stop delivery lag.
          │
          ├─► [Identity Protection Matrix]:
          │     ├── Email Exists? ──► Link dynamically to active User ID.
          │     └── Unknown Email? ─► Freeze profile creation. Save safely as Guest Ticket.
          │
          ├─► [Regex Context Cleanser]: Strips trailing historic blocks & quoted trails.
          │
          ▼
[MYSQL STORAGE] ──► [GEMINI AI] (Generates crisp issue summary & agent draft)
          │
          ▼ (Agent approves or reviews reply via Dashboard UI)
[LARAVEL SMTP ENGINE] (Injects original parent Message-ID into transport headers)
          │
          ▼ (Hardcodes verified sender: 2203051050509@paruluniversity.ac.in)
[CUSTOMER GMAIL INBOX] ◄── Perfect Inline Thread Collapse (No broken new email trails!)
```
## ✨ Key Features

### 👥 User & Role Management

The system enforces strict Role-Based Access Control (RBAC) with secure authorization boundaries:

- **Admin:** Complete system audit controls, team onboarding, database management, and global helpdesk reporting.
- **Support Agent:** Dedicated dashboard view optimized for viewing assigned tickets, reviewing customer timelines, executing AI drafts, and firing replies.

---

### 📥 Event-Driven Inbound Processing

An automated helpdesk framework backed by third-party mail streams that bridges direct customer email boxes with the web console:

- **Automated Webhook Ingestion:** Captures multi-part inbound email streams directly via Postmark cloud integration, converting raw MIME communications into structured database records in real-time.
- **Asynchronous HTTP Handshake:** Immediately issues a `200 OK` response back to network routing protocols, deferring heavy computation to background processors to eliminate server latency spikes.
- **Advanced Regex Context Cleansing:** Uses robust regular expressions to strip repetitive historical trails, metadata headers, and blockquotes (e.g., *"On Fri, 19 Jun 2026... wrote: >"*) from incoming mail bodies before committing to the database.

---

### 👤 Intelligent Identity Protection Matrix

- **Conditional Account Association:** Checks the sender's email address against the system records. If a matching registered user profile exists, the ticket links dynamically to their active account ID.
- **Secure Guest Pipeline Isolation:** If the email address is entirely unknown, the platform **strictly halts automatic user record creation**. Instead, it processes the entry as an unassigned Guest Ticket under a `null` user profile state to avoid database clutter.

---

### 📤 Outbound Conversational Threading Engine

- **Strict Sender Domain Compliance:** Hardcodes all outgoing transactional envelopes to an authorized, verified server identity signature (`2203051050509@paruluniversity.ac.in`) to ensure 100% inbox delivery rates.
- **SMTP Header Binding Injection:** Dynamically fetches the unique `Message-ID` from the original parent inbound email. It injects this token into the outgoing transport headers under standard `In-Reply-To` and `References` rules, forcing external clients like Gmail to collapse agent replies perfectly inside the user's pre-existing active email timeline thread instead of fragmenting it.

---

## 🤖 AI Features

### 1️⃣ AI Support Ticket Reply Generator
- Reads active conversation feeds inside an open ticket, calling the Gemini AI API to compose contextual, polite, and technical solution outlines. This decreases agent reaction times while enforcing a professional tone.

### 2️⃣ AI Ticket Summary & Insights
- Automatically analyzes long email ticket description chains using AI to extract key issue overviews, allowing support agents to get up to speed on complex issues in seconds before responding.

### 3️⃣ Token Optimization Layout
- By running the custom regex content cleanser prior to the AI processing layer, the pipeline strips out thousands of characters of trailing text noise, utilizing minimum context lengths and cutting API processing overhead.

---

## 📊 Dashboard & Insights

The analytics view tracks and displays critical key performance indicators (KPIs) in real-time:

- **Total Inbound Tickets:** Cumulative metric of all tickets successfully logged into the database.
- **Open vs. Resolved Ratios:** Distribution breakdown showing active helpdesk pipelines against closed tickets.
- **Guest Ticket Distribution:** Volume tracking of tickets initiated by unregistered email senders.
- **Average Interaction Depth:** Counter showing the message frequency inside conversational threads.

---

## 🔐 Role-Based Access Control

| Module / Permission | Admin | Support Agent | Guest / Customer (Email) |
| :--- | :---: | :---: | :---: |
| **User & Team Config** | ✓ Full Access | ✗ No Access | ✗ No Access |
| **Global Ticket Reports** | ✓ Full Access | ✗ No Access | ✗ No Access |
| **Ticket Assignment** | ✓ Full Access | ✗ No Access | ✗ No Access |
| **Manage Assigned Tickets** | ✓ Full Access | ✓ Full Access | ✗ No Access |
| **AI Draft Generation** | ✓ Full Access | ✓ Full Access | ✗ No Access |
| **Interact via Email** | ✗ No Access | ✗ No Access | ✓ Reply to Thread Only |

---

## 📱 Responsive UI Layout

The system dashboard is built with complete, user-friendly viewport responsiveness. The operational workflows, metrics graphs, and ticket conversation streams function smoothly across:
- Desktop Monitors & Workstations
- Laptops & Notebooks
- Tablets & Portables
- Handheld Mobile Devices

---

## 🛠️ Tools & Technologies Used

- **Laravel** – Core MVC backend architectural framework
- **PHP** – Server-side object-oriented programming language
- **MySQL** – Relational database management system
- **Blade** – Native Laravel layout compilation engine
- **Tailwind CSS** – Utility-first structural interface layouts
- **JavaScript** – Fluid UI dynamic updates and event operations
- **Postmark API** – Transactional mail delivery network and secure Inbound JSON processing streams
- **ngrok Tunneling** – Encrypted network bridging utility used to route cloud webhook requests to localized dev servers
- **Gemini AI API** – Natural language context processing for solution drafts and structural data mapping
- **Laravel Mail & Symfony Transport** – Native mail envelope composition and manual header mapping injections

---

## 🎯 Key Learnings

Developing this system provided advanced hands-on experience in executing:
- Enterprise cloud communication integrations using third-party RESTful mail streams.
- Manual manipulation of standard SMTP network transmission parameters (`In-Reply-To`, `References`) for client-side thread organization.
- Implementation of safe database isolation tactics to counter unauthenticated automated identity injections.
- Advanced pattern matching using Regex variables to clean bulk text structures prior to data storage.
- Building high-speed, event-driven web applications designed around non-blocking controller architectures.

---

## 📌 Project Type

Full Stack Web Development | Cloud-Mail Network Integration | Enterprise Automation System | Laravel Project

---

## 📷 Project Preview

### Helpdesk Dashboard
![Dashboard Preview](https://github.com/khansadiq5/ai-leadflow-crm/blob/main/public/screenshots/Dashboard.png)

### Ticket Management & Queue
![Ticket Preview](https://github.com/khansadiq5/ai-leadflow-crm/blob/main/public/screenshots/Tickets.png)

### AI Solution Generation
![AI Features Preview](https://github.com/khansadiq5/ai-leadflow-crm/blob/main/public/screenshots/AI%20Feature.png)

---

## ⚙️ Installation Guide

Clone the repository:
```bash
