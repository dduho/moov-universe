import { defineStore } from 'pinia';

const safeClone = (value) => {
  try {
    return structuredClone(value);
  } catch (error) {
    try {
      return JSON.parse(JSON.stringify(value));
    } catch (jsonError) {
      console.error('Clone error, returning original object', { error, jsonError });
      return value;
    }
  }
};

export const useAnalyticsCacheStore = defineStore('analyticsCache', {
  state: () => ({
    cache: {},
    monthlyRevenue: {}
  }),
  actions: {
    get(namespace, key) {
      const fullKey = `${namespace}:${key}`;
      return this.cache[fullKey] ? safeClone(this.cache[fullKey]) : null;
    },
    set(namespace, key, value) {
      const fullKey = `${namespace}:${key}`;
      this.cache[fullKey] = value;
    },
    getMonthly(namespace, year) {
      const fullKey = `${namespace}:monthly:${year}`;
      return this.monthlyRevenue[fullKey] ? safeClone(this.monthlyRevenue[fullKey]) : null;
    },
    setMonthly(namespace, year, data) {
      const fullKey = `${namespace}:monthly:${year}`;
      this.monthlyRevenue[fullKey] = data;
    },
    clearNamespace(namespace) {
      Object.keys(this.cache).forEach((key) => {
        if (key.startsWith(`${namespace}:`)) {
          delete this.cache[key];
        }
      });
      Object.keys(this.monthlyRevenue).forEach((key) => {
        if (key.startsWith(`${namespace}:monthly:`)) {
          delete this.monthlyRevenue[key];
        }
      });
    },
    clearAll() {
      this.cache = {};
      this.monthlyRevenue = {};
    }
  }
});
