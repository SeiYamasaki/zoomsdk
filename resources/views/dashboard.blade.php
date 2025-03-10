<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                </div>
                <div class="p-6 text-gray-900">
                    <a href="{{ route('bookings.list') }}" class="btn btn-primary">予約一覧に移動</a>
                    <a href="{{ route('meetings.register', ['id' => 1]) }}" class="btn btn-success">ミーティング参加登録</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
