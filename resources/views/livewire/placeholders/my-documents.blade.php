<div class="flex flex-col gap-6 animate-pulse">
    <div class="flex justify-between items-center gap-4">
        <div class="h-8 w-48 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
        <div class="h-8 w-32 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach(range(1, 6) as $i)
            <div class="h-32 bg-zinc-100 dark:bg-zinc-800 rounded-xl"></div>
        @endforeach
    </div>
</div>
