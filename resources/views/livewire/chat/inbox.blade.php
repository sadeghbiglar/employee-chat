<?php

use Livewire\Volt\Component;
use App\Models\Conversation;
use Illuminate\Support\Collection;

new class extends Component {
    public Collection $conversations;
    public ?string $activeConversationId = null;

    public function mount(): void
    {
        // دریافت لیست چت‌هایی که کاربر فعلی در آن‌ها عضو است به همراه آخرین پیام‌ها
        $this->conversations = auth()->user()->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }, 'users'])
            ->get();
    }

    public function selectConversation(string $id): void
    {
        $this->activeConversationId = $id;
        // در مراحل بعدی منطق بارگذاری پیام‌های چت انتخاب شده را اینجا می‌نویسیم
    }
}; ?>

<div class="flex h-[calc(100vh-4rem)] overflow-hidden bg-white dark:bg-gray-900 rounded-lg shadow-md">
    <div class="w-full md:w-1/3 border-l border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-800/50">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-3">گفتگوهای کارمندان</h1>
            <input type="text" placeholder="جستجوی همکاران یا گروه‌ها..." 
                   class="w-full px-4 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-gray-900 dark:text-gray-100" />
        </div>

        <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/50">
            @forelse($conversations as $conversation)
                @php
                    // پیدا کردن نام چت خصوصی (نام شخص مقابل)
                    $chatName = $conversation->is_group 
                        ? $conversation->name 
                        : $conversation->users->where('id', '!=', auth()->id())->first()?->name ?? 'کاربر ناشناس';
                    
                    $lastMessage = $conversation->messages->first();
                    $isActive = $activeConversationId === $conversation->id;
                @endphp

                <button wire:click="selectConversation('{{ $conversation->id }}')"
                        class="w-full text-right p-4 flex items-center justify-between transition-colors hover:bg-gray-100 dark:hover:bg-gray-700/50 {{ $isActive ? 'bg-blue-50 dark:bg-blue-900/30 border-r-4 border-blue-500' : '' }}">
                    <div class="flex items-center space-x-3 space-x-reverse min-w-0 flex-1">
                        <div class="relative flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold shadow-sm">
                                {{ mb_substr($chatName, 0, 2) }}
                            </div>
                            @if(!$conversation->is_group)
                                <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-800 bg-green-500"></span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ $chatName }}</h2>
                                @if($lastMessage)
                                    <span class="text-xs text-gray-400 dark:text-gray-500 pr-2">
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

    <div class="hidden md:flex md:w-2/3 flex-col bg-gray-50 dark:bg-gray-950 items-center justify-center p-8 text-center">
        @if($activeConversationId)
            <p class="text-gray-600 dark:text-gray-400">چت با شناسه {{ $activeConversationId }} انتخاب شد. در مرحله بعد سیستم نمایش پیام‌ها را تکمیل می‌کنیم.</p>
        @else
            <div class="max-w-sm flex flex-col items-center">
                <div class="w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.33.018.662.037.987.057v9.081a2.25 2.25 0 0 1-2.248 2.248H5.011a2.25 2.25 0 0 1-2.248-2.248V8.568c.325-.02.658-.039.987-.057M10.5 11.25h3M10.5 14.25h3M12 2.25v15m0 0-3-3m3 3 3-3" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">یک گفتگو را انتخاب کنید</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">برای شروع پیام‌رسانی، یکی از گفتگوهای سمت راست را انتخاب کنید یا یک گفتگوی جدید بسازید.</p>
            </div>
        @endif
    </div>
</div>