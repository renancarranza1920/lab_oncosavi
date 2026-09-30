@props(['status', 'columnWidth'])

<div class="{{ $columnWidth }} h-full flex flex-col">
    @include(static::$headerView, ['status' => $status])

    <div
        data-status-id="{{ $status['id'] }}"
        class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-3 flex flex-col gap-3"
        style="min-height: 200px;"
    >
        @foreach($status['records'] as $record)
            @include(static::$recordView, ['record' => $record])
        @endforeach
    </div>
</div>