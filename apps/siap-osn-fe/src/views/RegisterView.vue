<script setup>
import PublicNavbar from '@/components/PublicNavbar.vue'
import RegisterHeroPanel from '@/components/auth/RegisterHeroPanel.vue'
import RegisterForm from '@/components/auth/RegisterForm.vue'
</script>

<template>
  <div class="min-h-screen text-[#17233b] bg-white font-['Avenir_Next',Avenir,'Segoe_UI',sans-serif]">
    <PublicNavbar current="register" />

    <main class="grid grid-cols-1 min-[720px]:grid-cols-[1.05fr_0.95fr] min-h-[calc(100vh-72px)]">
      <RegisterHeroPanel />
      <RegisterForm />
    </main>
  </div>
</template>
