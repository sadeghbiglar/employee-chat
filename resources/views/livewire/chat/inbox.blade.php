<?php

use Livewire\Volt\Component;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;

new class extends Component {
    public Collection $conversations;
    public ?string $activeConversationId = null;
    public ?Conversation $selectedConversation = null;
    public Collection $chatMessages; // تغییر نام برای جلوگیری از تداخل با لایووایر

    #[Validate('required|string|max:2000')]
    public string $newMessage = '';

    public function mount(): void
    {
        $this->loadConversations();
        $this->chatMessages = collect();
    }

    public function loadConversations(): void
    {
        // دریافت لیست چت‌ها با جدیدترین پیام
        $this->conversations = auth()->user()->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }, 'users'])
            ->get();
    }

    public function selectConversation(string $id): void
    {
        $this->activeConversationId = $id;
        $this->selectedConversation = $this->conversations->firstWhere('id', $id);
        $this->loadMessages();
    }

    public function loadMessages(): void
    {
        if ($this->selectedConversation) {
            // دریافت تمام پیام‌های چت انتخابی به همراه اطلاعات فرستنده (برای جلوگیری از N+1 Query)
            $this->chatMessages = $this->selectedConversation->messages()
                ->with('sender')
                ->oldest()
                ->get();
        }
    }

    public function sendMessage(): void
    {
        $this->validate();

        if (!$this->selectedConversation) {
            return;
        }

        // ذخیره پیام در دیتابیس
        $message = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => auth()->id(),
            'body' => $this->newMessage,
            'type' => 'text',
        ]);

        // بارگذاری رابطه فرستنده روی پیام جدید و اضافه کردن آن به کالکشن پیام‌ها
        $message->load('sender');
        $this->chatMessages->push($message);

        // خالی کردن فیلد ورودی و بروزرسانی لیست سایدبار (برای نمایش آخرین پیام)
        $this->newMessage = '';
        $this->loadConversations();
    }
}; ?>

<div class="flex h-[calc(100vh-4rem)] overflow-hidden bg-white dark:bg-gray-900 rounded-lg shadow-md">
    <div class="w-full md:w-1/3 border-l border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-800/50">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-3">گفتگوهای کارمندان</h1>
            <input type="text" placeholder="جستجو..." 
                   class="w-full px-4 py-2 text-sm bg-gray-100 dark:bg-gray-800 border-none rounded-lg focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100 placeholder-gray-500" />
        </div>

        <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
            @forelse($conversations as $conversation)
                @php
                    $chatName = $conversation->is_group 
                        ? $conversation->name 
                        : $conversation->users->where('id', '!=', auth()->id())->first()?->name ?? 'کاربر ناشناس';
                    
                    $lastMessage = $conversation->messages->first();
                    $isActive = $activeConversationId === $conversation->id;
                @endphp

                <button wire:click="selectConversation('{{ $conversation->id }}')"
                        class="w-full text-right p-4 flex items-center justify-between transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 {{ $isActive ? 'bg-blue-50 dark:bg-gray-800 border-r-4 border-blue-500' : '' }}">
                    <div class="flex items-center space-x-3 space-x-reverse min-w-0 flex-1">
                        <div class="relative flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold shadow-sm">
                                {{ mb_substr($chatName, 0, 2) }}
                            </div>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $chatName }}</h2>
                                @if($lastMessage)
                                    <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap pr-2">
                                        {{ $lastMessage->created_at->format('H:i') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1">
                                @if($conversation->is_group && $lastMessage)
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $lastMessage->sender->name }}:</span>
                                @endif
                                {{ $lastMessage?->body ?? 'هنوز پیامی ارسال نشده است' }}
                            </p>
                        </div>
                    </div>
                </button>
            @empty
                <div class="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                    هیچ گفتگویی یافت نشد.
                </div>
            @endforelse
        </div>
    </div>

    <div class="hidden md:flex md:w-2/3 flex-col bg-[#e5ddd5] dark:bg-gray-950 relative">
        @if($activeConversationId && $selectedConversation)
            @php
                $headerName = $selectedConversation->is_group 
                    ? $selectedConversation->name 
                    : $selectedConversation->users->where('id', '!=', auth()->id())->first()?->name ?? 'کاربر ناشناس';
            @endphp

            <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm z-10">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
                        {{ mb_substr($headerName, 0, 2) }}
                    </div>
                    <div>
                        <h2 class="text-md font-semibold text-gray-900 dark:text-gray-100">{{ $headerName }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $selectedConversation->is_group ? $selectedConversation->users->count() . ' عضو' : 'آنلاین' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages-container">
                @foreach($chatMessages as $message)
                    @php
                        $isOwnMessage = $message->sender_id === auth()->id();
                    @endphp
                    
                    <div class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%] md:max-w-[60%] flex flex-col">
                            @if(!$isOwnMessage && $selectedConversation->is_group)
                                <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 mb-1 pr-1">{{ $message->sender->name }}</span>
                            @endif
                            
                            <div class="relative px-4 py-2 text-sm shadow-sm {{ $isOwnMessage ? 'bg-blue-500 text-white rounded-2xl rounded-tl-sm' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-2xl rounded-tr-sm' }}">
                                {{ $message->body }}
                                
                                <span class="block text-[10px] mt-1 text-right {{ $isOwnMessage ? 'text-blue-100' : 'text-gray-400 dark:text-gray-500' }}">
                                    {{ $message->created_at->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
                <form wire:submit="sendMessage" class="flex items-center space-x-2 space-x-reverse">
                    <button type="button" class="p-2 text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                    </button>

                    <input type="text" 
                           wire:model="newMessage" 
                           placeholder="پیام خود را بنویسید..." 
                           class="flex-1 px-4 py-3 bg-white dark:bg-gray-800 border-none rounded-full focus:ring-2 focus:ring-blue-500 text-sm text-gray-900 dark:text-gray-100 shadow-sm"
                           required />

                    <button type="submit" 
                            class="p-3 bg-blue-500 hover:bg-blue-600 text-white rounded-full transition-colors shadow-sm disabled:opacity-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 -ml-1">
                            <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z" />
                        </svg>
                    </button>
                </form>
            </div>
        @else
            <div class="flex-1 flex flex-col items-center justify-center text-center">
                <div class="w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.018.662.037.987.057v9.081a2.25 2.25 0 0 1-2.248 2.248H5.011a2.25 2.25 0 0 1-2.248-2.248V8.568c.325-.02.658-.039.987-.057M10.5 11.25h3M10.5 14.25h3M12 2.25v15m0 0-3-3m3 3 3-3" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">یک گفتگو را انتخاب کنید</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">برای شروع پیام‌رسانی، روی یکی از گفتگوهای سمت راست کلیک کنید.</p>
            </div>
        @endif
    </div>
</div>