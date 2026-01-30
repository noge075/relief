<div class="flex flex-col gap-6 animate-pulse">
    <div class="flex justify-between items-center gap-4">
        <div class="h-8 w-48 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
        <div class="h-8 w-32 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
    </div>

    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
        <div class="h-12 bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700"></div>
        @foreach(range(1, 10) as $i)
            <div class="h-12 border-b border-zinc-100 dark:border-zinc-800 bg-white dark:bg-zinc-900"></div>
        @endforeach
    </div>
</div>
