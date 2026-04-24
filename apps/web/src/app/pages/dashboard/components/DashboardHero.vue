<script setup lang="ts">
defineEmits<{
  start: []
}>()
</script>

<template>
  <header class="border-lab-line/15 flex flex-col gap-5 border-b pb-6 md:flex-row md:items-center md:justify-between">
    <div>
      <p class="text-sm font-semibold tracking-normal text-emerald-800 uppercase">Premium training lab</p>
      <h1
        class="text-lab-shell mt-2 max-w-3xl text-3xl leading-tight font-semibold tracking-normal sm:text-4xl lg:text-5xl"
      >
        Today&apos;s work is ready to log.
      </h1>
      <p class="text-lab-copy mt-3 max-w-2xl text-base leading-7">
        Calisthenics progress, readiness, and recommendation gates in one focused workspace.
      </p>
    </div>
    <button
      class="bg-lab-emerald shadow-lab-control hover:bg-lab-emerald-strong focus-visible:ring-offset-lab-paper inline-flex min-h-11 items-center justify-center rounded-lg px-5 py-3 text-sm font-semibold text-white transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
      type="button"
      @click="$emit('start')"
    >
      Start workout
    </button>
  </header>
</template>
