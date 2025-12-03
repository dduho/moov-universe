<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <router-link to="/dashboard" class="text-xl font-bold text-moov-orange">
              Moov Money Universe
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <div class="py-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Détail du PDV</h1>
        <div class="bg-white shadow rounded-lg p-6">
          <p class="text-gray-600">Détails du PDV - À implémenter</p>
          <router-link to="/pdv/list" class="mt-4 inline-block text-moov-orange hover:text-moov-orange-dark">
            ← Retour à la liste
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
</script>
