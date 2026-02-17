<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white 
               transition-all duration-300 ease-in-out 
               hover:bg-red-500 hover:shadow-[0_0_20px_rgba(239,68,68,0.6)] hover:border-red-400 hover:scale-105
               focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:bg-red-700"
    >
        <slot />
    </button>
</template>