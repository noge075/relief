<div class="flex flex-col gap-6 w-full animate-pulse">
    <div class="flex justify-between items-center mb-4">
        <div class="h-8 w-32 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
        <div class="h-8 w-48 bg-zinc-200 dark:bg-zinc-700 rounded"></div>
    </div>

    <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden">
        <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-zinc-700">
            @foreach(range(1, 7) as $i)
                <div class="py-3 text-center">
                    <div class="h-4 w-8 bg-zinc-200 dark:bg-zinc-700 rounded mx-auto"></div>
                </div>
            @endforeach
        </div>
        <div class="grid grid-cols-7 h-125">
            @foreach(range(1, 35) as $i)
                <div class="border-r border-b border-zinc-100 dark:border-zinc-800 p-2">
                    <div class="h-6 w-6 bg-zinc-100 dark:bg-zinc-800 rounded-full mb-2"></div>
                </div>
            @endforeach
        </div>
    </div>
</div>
