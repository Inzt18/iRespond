<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Notifications')] class extends Component {
    use WithPagination;

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
        $this->resetPage();
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
                ? $user->notifications()->latest()->paginate(20)
                : collect(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-4 md:p-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="font-display text-xs font-semibold uppercase tracking-[0.18em] text-emergency">{{ __('Inbox') }}</p>
            <flux:heading size="xl" class="font-display mt-1 text-3xl tracking-wide">{{ __('Notifications') }}</flux:heading>
            <flux:text class="mt-1">
                {{ __('Alerts for new emergency reports and admin actions.') }}
            </flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:badge color="red" size="sm">
                {{ __(':count unread', ['count' => $unreadCount]) }}
            </flux:badge>
            @if ($unreadCount > 0)
                <flux:button size="sm" variant="primary" wire:click="markAllAsRead">
                    {{ __('Mark all as read') }}
                </flux:button>
            @endif
        </div>
    </div>

    <flux:card>
        <div class="divide-y divide-zinc-100">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = $notification->read_at === null;
                @endphp
                <button
                    type="button"
                    wire:click="openNotification('{{ $notification->id }}')"
                    class="flex w-full items-start gap-3 px-2 py-4 text-start transition hover:bg-zinc-50 {{ $isUnread ? 'bg-emergency/5' : '' }}"
                >
                    <div class="mt-1.5 size-2.5 shrink-0 rounded-full {{ $isUnread ? 'bg-emergency' : 'bg-zinc-200' }}"></div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <flux:heading size="sm" class="{{ $isUnread ? 'font-semibold' : '' }}">
                                {{ $data['title'] ?? __('Notification') }}
                            </flux:heading>
                            <flux:text class="text-xs">
                                {{ $notification->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            </flux:text>
                        </div>
                        <flux:text class="mt-1">
                            {{ $data['message'] ?? '' }}
                        </flux:text>
                        @if (! empty($data['confirmed_type']))
                            <flux:text class="mt-1 text-xs capitalize">
                                {{ str_replace('_', ' ', $data['confirmed_type']) }}
                                · {{ $data['status'] ?? '' }}
                            </flux:text>
                        @endif
                    </div>
                </button>
            @empty
                <div class="px-2 py-10 text-center text-sm text-zinc-500">
                    {{ __('No notifications yet. New citizen emergency reports will appear here.') }}
                </div>
            @endforelse
        </div>

        @if (method_exists($notifications, 'links'))
            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </flux:card>
</div>
