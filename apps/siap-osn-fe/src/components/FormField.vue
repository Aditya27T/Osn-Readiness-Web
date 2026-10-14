<script setup>
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'

defineProps({
  label: { type: String, required: true },
  error: { type: String, default: '' },
  modelValue: { type: String, default: '' },
  type: { type: String, default: 'text' },
  inputId: { type: String, required: true },
})

defineEmits(['update:modelValue'])
</script>

<template>
  <div class="flex flex-col gap-1">
    <label :for="inputId">{{ label }}</label>
    <Password
      v-if="type === 'password'"
      :input-id="inputId"
      :model-value="modelValue"
      :feedback="false"
      toggle-mask
      fluid
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <InputText
      v-else
      :id="inputId"
      :model-value="modelValue"
      fluid
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <small v-if="error" class="text-red-500">{{ error }}</small>
  </div>
</template>
