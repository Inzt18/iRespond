<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    public function markAsRead(string $notificationId): void
    {
        $notification = Auth::user()?->notifications()->whereKey($notificationId)->first();

        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();
    }

    public function openNotification(string $notificationId): mixed
    {
        $notification = Auth::user()?->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            return null;
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $url = $notification->data['url'] ?? route('admin.reports');

        return $this->redirect($url, navigate: true);
    }

    public function with(): array
    {
        $user = Auth::user();

        return [
            'unreadCount' => $user?->unreadNotifications()->count() ?? 0,
            'notifications' => $user
                ? $user->notifications()->latest()->limit(8)->get()
                : collect(),
        ];
    }
}; ?>

<div wire:poll.30s class="relative">
    <flux:dropdown position="bottom" align="end">
        <flux:button
            variant="ghost"
            size="sm"
            icon="bell"
            class="relative"
            :aria-label="__('Notifications')"
        >
            @if ($unreadCount > 0)
                <span class="absolute -end-0.5 -top-0.5 inline-flex min-w-4 items-center justify-center rounded-full bg-emergency px-1 text-[10px] font-bold leading-4 text-white">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-[22rem] max-w-[90vw]">
            <div class="flex items-center justify-between gap-2 px-3 py-2">
                <flux:heading size="sm">{{ __('Notifications') }}</flux:heading>
                @if ($unreadCount > 0)
                    <flux:button size="xs" variant="ghost" wire:click="markAllAsRead">
                        {{ __('Mark all read') }}
                    </flux:button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = $notification->read_at === null;
                @endphp
                <flux:menu.item
                    wire:click="openNotification('{{ $notification->id }}')"
                    class="cursor-pointer {{ $isUnread ? 'bg-emergency/5' : '' }}"
                >
                    <div class="flex w-full flex-col gap-0.5 py-0.5">
                        <div class="flex items-start justify-between gap-2">
                            <span @class([
                                'text-sm text-zinc-900',
                                'font-semibold' => $isUnread,
                                'font-medium' => ! $isUnread,
                            ])>
                                {{ $data['title'] ?? __('Notification') }}
                            </span>
                            @if ($isUnread)
                                <span class="mt-1 size-1.5 shrink-0 rounded-full bg-emergency"></span>
                            @endif
                        </div>
                        <span class="text-xs text-zinc-600">
                            {{ $data['message'] ?? '' }}
                        </span>
                        <span class="text-[11px] text-zinc-400">
                            {{ $notification->created_at?->diffForHumans() }}
                        </span>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-6 text-center text-sm text-zinc-500">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse

            <flux:menu.separator />

            <flux:menu.item :href="route('admin.notifications')" icon="inbox" wire:navigate>
                {{ __('View all notifications') }}
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
