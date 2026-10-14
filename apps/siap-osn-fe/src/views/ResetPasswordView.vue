<script setup>
import PublicNavbar from '@/components/PublicNavbar.vue'
import RecoveryHeroPanel from '@/components/auth/RecoveryHeroPanel.vue'
import ResetPasswordForm from '@/components/auth/ResetPasswordForm.vue'
</script>

<template>
  <div class="min-h-screen text-[#17233b] bg-white font-['Avenir_Next',Avenir,'Segoe_UI',sans-serif]">
    <PublicNavbar current="login" />

    <main
      class="grid grid-cols-1 min-[720px]:grid-cols-[1.05fr_0.95fr] min-h-[calc(100vh-72px)]"
    >
      <RecoveryHeroPanel />
      <ResetPasswordForm />
    </main>
  </div>
</template>