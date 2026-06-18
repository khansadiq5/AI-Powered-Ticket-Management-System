<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ticket->ticket_number }} — {{ $ticket->subject }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.9/purify.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }

        /* Priority badge colors */
        .badge-urgent { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-high   { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .badge-medium { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-low    { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }

        /* Status badge colors */
        .badge-open        { background: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
        .badge-in_progress { background: #fef9c3; color: #854d0e; border: 1px solid #fef08a; }
        .badge-resolved    { background: #f0fdf4; color: #166534; border: 1px solid #dcfce7; }
        .badge-closed      { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }

        .email-body {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.7;
        }

        /* AI summary container */
        .ai-card {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-900 bg-slate-50">

    <!-- Navbar -->
    <nav class="bg-white border-b border-slate-200/80 py-4 px-6 md:px-8 flex justify-between items-center sticky top-0 z-40">
        <a href="{{ $user->role === 'admin' ? '/admin' : '/agent' }}" class="text-lg font-bold tracking-wider text-slate-900">
            {{ $user->role === 'admin' ? 'TICKET SYSTEM' : 'AGENT PANEL' }}
        </a>
        <div class="flex items-center gap-4">
            <a href="{{ $user->role === 'admin' ? '/admin/tickets' : '/agent' }}" class="text-slate-500 hover:text-slate-800 text-sm font-medium transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Tickets
            </a>
            <form method="POST" action="/logout" class="m-0">
                @csrf
                <button type="submit" class="bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 font-medium rounded-lg py-1.5 px-4 text-xs transition cursor-pointer">Sign Out</button>
            </form>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1 py-10 px-4 md:px-8 max-w-5xl mx-auto w-full">

        <!-- Flash Message -->
        @if(session('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif

        <!-- Ticket Header -->
        <div class="mb-8">
            <div class="flex flex-wrap items-center gap-2.5 mb-3">
                <span class="font-mono text-sm text-slate-900 font-semibold bg-slate-200/60 px-3 py-1 rounded-md border border-slate-300/45">{{ $ticket->ticket_number }}</span>
                <span class="badge-{{ $ticket->priority }} text-xs px-2.5 py-0.5 rounded border font-semibold uppercase tracking-wider">{{ $ticket->priority }}</span>
                <span id="ticket-status-badge" class="badge-{{ $ticket->status }} text-xs px-2.5 py-0.5 rounded border font-semibold capitalize">{{ str_replace('_', ' ', $ticket->status) }}</span>
                <span id="ticket-category-badge" class="bg-indigo-50 text-indigo-700 border border-indigo-100 text-xs px-2.5 py-0.5 rounded font-semibold">{{ $ticket->category ?? 'General' }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 leading-snug">{{ $ticket->subject }}</h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- AI Summary -->
                @if($ticket->ai_summary)
                <div class="ai-card rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-2.5">
                        <span class="text-base">🤖</span>
                        <h3 class="text-xs font-semibold text-emerald-800 uppercase tracking-wider">AI Generated Summary</h3>
                    </div>
                    <p class="text-sm text-emerald-950 leading-relaxed">{{ $ticket->ai_summary }}</p>
                </div>
                @endif

                <!-- Email Body -->
                <div class="bg-white border border-slate-200 rounded-xl p-6">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Email Message</h3>
                    <div class="email-body text-sm text-slate-800">{{ $ticket->body }}</div>
                </div>

                <!-- Reply Thread Section -->
                <div class="bg-white border border-slate-200 rounded-xl p-6 space-y-6" id="replies-section">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Conversation Thread</h3>

                    <div id="no-replies-message" class="{{ $ticket->replies->isEmpty() ? '' : 'hidden' }} text-center py-6 text-slate-400 text-sm">
                        No replies posted yet. Use the form below to start the conversation.
                    </div>

                    <div class="space-y-4 {{ $ticket->replies->isEmpty() ? 'hidden' : '' }}" id="replies-container">
                        @foreach($ticket->replies as $reply)
                            <div class="flex items-start gap-3.5 p-4 rounded-xl border border-slate-100 bg-slate-50/50">
                                <!-- User Avatar (Initials) -->
                                <div class="w-8 h-8 rounded-full bg-slate-200/80 border border-slate-300/30 flex items-center justify-center font-semibold text-xs text-slate-700 flex-shrink-0">
                                    {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-slate-900">{{ $reply->user->name }}</span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider {{ $reply->user->role === 'admin' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-blue-50 text-blue-600 border border-blue-100' }}">
                                                {{ $reply->user->role }}
                                            </span>
                                        </div>
                                        <span class="text-xs text-slate-400">{{ $reply->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-sm text-slate-800 mt-2 leading-relaxed whitespace-pre-line">{{ $reply->body }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Submit Reply Form -->
                <div class="bg-white border border-slate-200 rounded-xl p-6">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Post a Reply</h3>
                    <form method="POST" action="/agent/tickets/{{ $ticket->id }}/replies" id="reply-form" class="m-0">
                        @csrf
                        <div class="mb-4">
                            <textarea name="body" id="reply-body" rows="4" required placeholder="Type your reply here..." 
                                      class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 p-3.5 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 placeholder:text-slate-400 resize-none"></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-6 text-sm transition cursor-pointer">
                                Submit Reply
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="space-y-6">
                <!-- Sender Info -->
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Sender</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-semibold text-sm text-slate-700 flex-shrink-0">
                            {{ strtoupper(substr($ticket->sender_name ?? $ticket->sender_email, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-slate-900 truncate">{{ $ticket->sender_name ?? 'Unknown Sender' }}</div>
                            <div class="text-xs text-slate-500 truncate mt-0.5" title="{{ $ticket->sender_email }}">{{ $ticket->sender_email }}</div>
                        </div>
                    </div>
                </div>

                <!-- Ticket Details -->
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Metadata</h3>
                    <div class="space-y-3.5 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 text-xs">Created</span>
                            <span class="text-slate-800 font-medium text-xs">{{ $ticket->created_at->format('M d, Y · H:i') }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 text-xs">Updated</span>
                            <span class="text-slate-800 font-medium text-xs">{{ $ticket->updated_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2.5 border-t border-slate-100" id="assigned-agent-row">
                            <span class="text-slate-500 text-xs">Assigned agent</span>
                            <span class="text-slate-800 font-semibold text-xs" id="assigned-agent-name">{{ $ticket->assignedAgent ? $ticket->assignedAgent->name : 'Unassigned' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status Update Form -->
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Update Status</h3>
                    <form method="POST" action="/agent/tickets/{{ $ticket->id }}/status" id="status-form" class="m-0">
                        @csrf
                        @method('PATCH')
                        <div class="relative mb-3">
                            <select name="status" id="status-select"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 appearance-none cursor-pointer pr-10">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                            <!-- Arrow icon -->
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <button type="submit"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-4 text-sm transition cursor-pointer">
                            Save Status
                        </button>
                    </form>
                </div>

                <!-- Category Update Form -->
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Update Category</h3>
                    <form method="POST" action="/agent/tickets/{{ $ticket->id }}/category" id="category-form" class="m-0">
                        @csrf
                        @method('PATCH')
                        <div class="relative mb-3">
                            <select name="category" id="category-select"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 appearance-none cursor-pointer pr-10">
                                <option value="General" {{ ($ticket->category ?? 'General') === 'General' ? 'selected' : '' }}>General</option>
                                <option value="Refund" {{ $ticket->category === 'Refund' ? 'selected' : '' }}>Refund</option>
                                <option value="Technical" {{ $ticket->category === 'Technical' ? 'selected' : '' }}>Technical</option>
                            </select>
                            <!-- Arrow icon -->
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <button type="submit"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-4 text-sm transition cursor-pointer">
                            Save Category
                        </button>
                    </form>
                </div>

                {{-- Assign Agent Form (Admin Only) --}}
                @if($user->role === 'admin' && isset($agents))
                <div class="bg-white border border-slate-200 rounded-xl p-5">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Assign Agent</h3>
                    <form method="POST" action="/admin/tickets/{{ $ticket->id }}/assign" id="assign-form" class="m-0">
                        @csrf
                        @method('PATCH')
                        <div class="relative mb-3">
                            <select name="assigned_to" id="assigned-to-select"
                                    class="w-full bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-900 py-2.5 px-4 focus:outline-none focus:ring-1 focus:ring-slate-400 focus:border-slate-400 appearance-none cursor-pointer pr-10">
                                <option value="">Unassigned</option>
                                @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                            <!-- Arrow icon -->
                            <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <button type="submit"
                                class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg py-2.5 px-4 text-sm transition cursor-pointer">
                            Assign Agent
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </main>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Helper to show custom premium toasts
            function showToast(message, type = 'success') {
                const container = document.getElementById('toast-container');
                if (!container) return;
                
                const toast = document.createElement('div');
                toast.className = `transform translate-y-2 opacity-0 transition-all duration-300 pointer-events-auto px-4 py-3 rounded-lg shadow-lg text-sm font-semibold flex items-center gap-2 border ${
                    type === 'success' 
                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200' 
                        : 'bg-rose-50 text-rose-800 border-rose-200'
                }`;
                
                const icon = type === 'success'
                    ? `<svg class="w-4.5 h-4.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
                    : `<svg class="w-4.5 h-4.5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
                    
                toast.innerHTML = `${icon} <span>${message}</span>`;
                container.appendChild(toast);
                
                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-y-2', 'opacity-0');
                }, 10);
                
                // Animate out and remove
                setTimeout(() => {
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 3000);
            }

            // Helper to send AJAX requests
            async function sendAjaxRequest(form, onSuccess) {
                const url = form.getAttribute('action');
                const formData = new FormData(form);
                
                // Always use POST for FormData requests so PHP parses the body correctly.
                // Laravel routes PATCH/PUT endpoints using the hidden _method field.
                const method = 'POST';

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    // Mini spinner
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> Saving...`;
                }

                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': formData.get('_token')
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        showToast(data.message || 'Updated successfully.');
                        onSuccess(data);
                    } else {
                        showToast(data.message || 'An error occurred. Please try again.', 'error');
                    }
                } catch (error) {
                    console.error(error);
                    showToast('Network error. Please check your connection.', 'error');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            }

            // Intercept Status Form
            const statusForm = document.getElementById('status-form');
            if (statusForm) {
                statusForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(statusForm, (data) => {
                        const badge = document.getElementById('ticket-status-badge');
                        if (badge) {
                            badge.textContent = data.status_label;
                            badge.className = `badge-${data.status} text-xs px-2.5 py-0.5 rounded border font-semibold capitalize`;
                        }
                    });
                });
            }

            // Intercept Category Form
            const categoryForm = document.getElementById('category-form');
            if (categoryForm) {
                categoryForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(categoryForm, (data) => {
                        const badge = document.getElementById('ticket-category-badge');
                        if (badge) {
                            badge.textContent = data.category;
                        }
                    });
                });
            }

            // Intercept agent assign form
            const assignForm = document.getElementById('assign-form');
            if (assignForm) {
                assignForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(assignForm, (data) => {
                        const agentNameEl = document.getElementById('assigned-agent-name');
                        if (agentNameEl) {
                            agentNameEl.textContent = data.assigned_agent_name;
                        }
                    });
                });
            }

            // Intercept Replies Form
            const replyForm = document.getElementById('reply-form');
            if (replyForm) {
                replyForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    sendAjaxRequest(replyForm, (data) => {
                        const noRepliesMsg = document.getElementById('no-replies-message');
                        if (noRepliesMsg) {
                            noRepliesMsg.classList.add('hidden');
                        }

                        const repliesContainer = document.getElementById('replies-container');
                        if (repliesContainer) {
                            repliesContainer.classList.remove('hidden');

                            // Create new reply element
                            const replyDiv = document.createElement('div');
                            replyDiv.className = 'flex items-start gap-3.5 p-4 rounded-xl border border-slate-100 bg-slate-50/50 transform scale-95 opacity-0 transition-all duration-300';
                            
                            const cleanInitial = DOMPurify.sanitize(data.reply.user.initial);
                            const cleanName = DOMPurify.sanitize(data.reply.user.name);
                            const cleanRole = DOMPurify.sanitize(data.reply.user.role);
                            const cleanTime = DOMPurify.sanitize(data.reply.created_at_human);
                            const cleanBody = DOMPurify.sanitize(data.reply.body);

                            const roleBadgeClass = cleanRole === 'admin' 
                                ? 'bg-rose-50 text-rose-600 border border-rose-100' 
                                : 'bg-blue-50 text-blue-600 border border-blue-100';

                            replyDiv.innerHTML = `
                                <div class="w-8 h-8 rounded-full bg-slate-200/80 border border-slate-300/30 flex items-center justify-center font-semibold text-xs text-slate-700 flex-shrink-0">
                                    ${cleanInitial}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-slate-900">${cleanName}</span>
                                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider ${roleBadgeClass}">
                                                ${cleanRole}
                                            </span>
                                        </div>
                                        <span class="text-xs text-slate-400">${cleanTime}</span>
                                    </div>
                                    <div class="text-sm text-slate-800 mt-2 leading-relaxed whitespace-pre-line">${cleanBody}</div>
                                </div>
                            `;

                            repliesContainer.appendChild(replyDiv);
                            
                            // Simple animation
                            setTimeout(() => {
                                replyDiv.classList.remove('scale-95', 'opacity-0');
                            }, 50);
                        }

                        // Reset body text
                        const replyBody = document.getElementById('reply-body');
                        if (replyBody) {
                            replyBody.value = '';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
