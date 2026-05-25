<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ۱. ساخت کارمندان تستی
        $user1 = User::factory()->create([
            'name' => 'احمد رضایی',
            'email' => 'ahmad@company.com',
            'password' => Hash::make('password'),
        ]);

        $user2 = User::factory()->create([
            'name' => 'سارا علوی',
            'email' => 'sara@company.com',
            'password' => Hash::make('password'),
        ]);

        $user3 = User::factory()->create([
            'name' => 'محمد کریمی',
            'email' => 'mohammad@company.com',
            'password' => Hash::make('password'),
        ]);

        // ۲. ساخت یک چت خصوصی (1-on-1) بین احمد و سارا
        $privateChat = Conversation::create([
            'is_group' => false,
            'name' => null,
        ]);
        $privateChat->users()->attach([$user1->id, $user2->id]);

        // ارسال پیام‌های تستی در چت خصوصی
        Message::create([
            'conversation_id' => $privateChat->id,
            'sender_id' => $user1->id,
            'body' => 'سلام سارا خانم، گزارش فروش ماهانه آماده شده؟',
            'type' => 'text',
        ]);

        Message::create([
            'conversation_id' => $privateChat->id,
            'sender_id' => $user2->id,
            'body' => 'سلام آقای رضایی، بله تا نیم ساعت دیگه براتون می‌فرستم.',
            'type' => 'text',
        ]);

        // ۳. ساخت یک چت گروهی (تیم فنی) با حضور همه کاربران
        $groupChat = Conversation::create([
            'is_group' => true,
            'name' => 'تیم توسعه محصول 🚀',
        ]);
        $groupChat->users()->attach([$user1->id, $user2->id, $user3->id]);

        // ارسال پیام تستی در گروه
        Message::create([
            'conversation_id' => $groupChat->id,
            'sender_id' => $user3->id,
            'body' => 'سلام همکاران گرامی، جلسه فنی ساعت ۱۴ برگزار میشه.',
            'type' => 'text',
        ]);
    }
}