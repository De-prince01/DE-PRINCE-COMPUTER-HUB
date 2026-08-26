<template>
  <div class="max-w-2xl mx-auto px-4 py-16">
    <div class="card p-8 text-center">
      <div class="text-sm font-semibold text-slate-600">404</div>
      <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-slate-900">Page not found</h1>
      <p class="mt-2 text-sm text-slate-600">The page you’re looking for doesn’t exist.</p>
      <div class="mt-6 flex justify-center gap-2">
        <router-link to="/" class="btn btn-primary">Go Home</router-link>
        <router-link to="/login" class="btn btn-secondary">Login</router-link>
      </div>
    </div>
  </div>
</template>
