<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white 
               transition-all duration-300 ease-in-out 
               hover:bg-blue-500 hover:shadow-[0_0_20px_rgba(59,130,246,0.6)] hover:border-blue-400 hover:scale-105
               focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 active:bg-blue-700"
    >
        <slot />
    </button>
</template>