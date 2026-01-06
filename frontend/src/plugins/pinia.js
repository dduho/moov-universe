import { createPinia } from 'pinia';

// Single pinia instance shared across app and router guards
const pinia = createPinia();

export default pinia;
