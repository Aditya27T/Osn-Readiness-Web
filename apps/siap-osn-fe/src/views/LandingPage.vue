<script setup>
import PublicNavbar from '@/components/PublicNavbar.vue'
import PublicFooter from '@/components/PublicFooter.vue'
import HeroSection from '@/components/landing/HeroSection.vue'
import ProblemSection from '@/components/landing/ProblemSection.vue'
import SolutionSection from '@/components/landing/SolutionSection.vue'
import StepsSection from '@/components/landing/StepsSection.vue'
import FeaturesSection from '@/components/landing/FeaturesSection.vue'
import LevelsSection from '@/components/landing/LevelsSection.vue'
import CtaSection from '@/components/landing/CtaSection.vue'

const navLinks = [
  { label: 'Kenapa SIAP OSN', href: '#kenapa' },
  { label: 'Cara Kerja', href: '#cara-kerja' },
  { label: 'Fitur', href: '#fitur' },
  { label: 'Tingkat Seleksi', href: '#tingkat' },
  { label: 'Tim Kami', href: '#tim' },
]
</script>

<template>
  <div class="lp">
    <PublicNavbar :links="navLinks" current="landing" theme="dark" />
    <HeroSection />
    <ProblemSection />
    <SolutionSection />
    <StepsSection />
    <FeaturesSection />
    <LevelsSection />
    <CtaSection />
    <PublicFooter />
  </div>
</template>

<style scoped>

.lp {
  --navy: #0a1d3d;
  --navy-2: #0e2a52;
  --navy-3: #12284f;
  --ink: #0f1b33;
  --muted: #5b6784;
  --soft: #edf0f8;
  --line: #e3e7f0;
  --blue: #3b82f6;
  --yellow: #f5c96a;
  --green: #38b27a;
  --radius: 10px;

  font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
  color: var(--ink);
  line-height: 1.55;
  -webkit-font-smoothing: antialiased;
}
.lp *,
.lp *::before,
.lp *::after {
  box-sizing: border-box;
}
.lp h1,
.lp h2,
.lp h3 {
  font-family: 'Space Grotesk', 'Inter', system-ui, sans-serif;
  font-weight: 700;
  line-height: 1.15;
  margin: 0;
  letter-spacing: -0.01em;
}
.lp p {
  margin: 0;
}
.lp a:not(.btn) {
  color: inherit;
  text-decoration: none;
}
</style>
