<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Comparateur de Performance</h1>
        <p class="text-sm text-gray-600 mt-1">Comparez jusqu'à 4 entités côte à côte</p>
      </div>

      <!-- Configuration Panel -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 mb-6 relative z-40">
        <!-- Type Selection -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Type de comparaison</label>
            <select 
              v-model="comparisonType" 
              @change="resetSelection"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
            >
              <option value="pdv">PDV</option>
              <option value="dealer">Dealers</option>
              <option value="period">Périodes</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Période d'analyse</label>
            <select 
              v-model="period"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
            >
              <option value="day">Jour</option>
              <option value="week">Semaine</option>
              <option value="month">Mois</option>
              <option value="quarter">Trimestre</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Date de référence</label>
            <input 
              type="date" 
              v-model="referenceDate"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
            />
          </div>
        </div>

        <!-- Entity Selection -->
        <div class="mb-4 relative z-50">
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            Sélectionner {{ getEntityLabel() }} (2 à 4)
          </label>
          
          <!-- PDV/Dealer Search -->
          <div v-if="comparisonType !== 'period'" class="relative">
            <input 
              ref="searchInput"
              type="text" 
              v-model="searchQuery"
              @input="handleSearch"
              :placeholder="`Rechercher ${getEntityLabel()}...`"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
            />
            
            <!-- Search Results -->
            <div v-if="searchResults.length > 0" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
              <button
                v-for="item in searchResults"
                :key="item.id"
                @click="addEntity(item)"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center justify-between"
              >
                <div>
                  <div class="font-semibold">{{ item.name }}</div>
                  <div class="text-xs text-gray-500">{{ item.subtitle }}</div>
                </div>
                <svg v-if="isSelected(item.id)" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Period Selection -->
          <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div v-for="i in 4" :key="i">
              <input 
                type="text" 
                v-model="periodInputs[i-1]"
                placeholder="ex: 2025-12"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
              />
              <p class="text-xs text-gray-500 mt-1">Format: YYYY-MM, YYYY-Q1, YYYY-W01</p>
            </div>
          </div>
        </div>

        <!-- Selected Entities -->
        <div v-if="selectedEntities.length > 0" class="mb-4">
          <div class="flex flex-wrap gap-2">
            <div 
              v-for="entity in selectedEntities"
              :key="entity.id"
              class="flex items-center gap-2 px-3 py-1 bg-moov-orange/10 text-moov-orange rounded-full"
            >
              <span class="font-semibold">{{ entity.name }}</span>
              <button @click="removeEntity(entity.id)" class="hover:bg-moov-orange/20 rounded-full p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Compare Button -->
        <div class="flex gap-3">
          <button
            @click="performComparison"
            :disabled="!canCompare || loading"
            class="flex-1 px-6 py-3 bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white rounded-xl font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="!loading">🔍 Comparer ({{ selectedCount }}/4)</span>
            <span v-else>⏳ Chargement...</span>
          </button>

          <button
            v-if="comparisonResults"
            @click="exportToPDF"
            class="px-6 py-3 bg-white border-2 border-moov-orange text-moov-orange rounded-xl font-semibold hover:bg-moov-orange/5 transition-all"
          >
            📄 Export PDF
          </button>
        </div>
      </div>

      <!-- Comparison Results -->
      <div v-if="comparisonResults && !loading" class="space-y-6">
        <!-- Metrics Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          <div 
            v-for="(result, index) in comparisonResults.comparisons"
            :key="result.id"
            class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6"
          >
            <!-- Header -->
            <div class="mb-4 pb-4 border-b border-gray-200">
              <h3 class="font-bold text-lg text-gray-900 truncate">{{ result.name }}</h3>
              <div v-if="result.info" class="text-xs text-gray-500 mt-1 space-y-1">
                <div v-for="(value, key) in result.info" :key="key">
                  <span class="capitalize">{{ formatKey(key) }}:</span> {{ value }}
                </div>
              </div>
            </div>

            <!-- Metrics -->
            <div class="space-y-3">
              <div>
                <div class="text-xs text-gray-600 mb-1">Chiffre d'Affaires</div>
                <div class="text-2xl font-bold text-moov-orange">{{ formatCurrency(result.metrics.ca) }}</div>
                <div v-if="index === 0" class="text-xs text-gray-500 mt-1">🏆 Référence</div>
                <div v-else class="text-xs mt-1" :class="getComparisonClass(result.metrics.ca, comparisonResults.comparisons[0].metrics.ca)">
                  {{ getComparisonText(result.metrics.ca, comparisonResults.comparisons[0].metrics.ca) }}
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div>
                  <div class="text-xs text-gray-600 mb-1">Volume</div>
                  <div class="text-lg font-bold text-gray-900">{{ formatCurrency(result.metrics.volume) }}</div>
                </div>
                <div>
                  <div class="text-xs text-gray-600 mb-1">Transactions</div>
                  <div class="text-lg font-bold text-gray-900">{{ formatNumber(result.metrics.transactions) }}</div>
                </div>
              </div>

              <div v-if="result.metrics.depots !== undefined">
                <div class="text-xs text-gray-600 mb-1">Dépôts / Retraits</div>
                <div class="text-sm font-semibold text-gray-900">
                  {{ formatNumber(result.metrics.depots) }} / {{ formatNumber(result.metrics.retraits) }}
                </div>
              </div>

              <div v-if="result.metrics.commission !== undefined">
                <div class="text-xs text-gray-600 mb-1">Commission</div>
                <div class="text-sm font-semibold text-green-600">{{ formatCurrency(result.metrics.commission) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Evolution Chart -->
        <div v-if="chartData" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Évolution du Chiffre d'Affaires</h3>
          <div class="h-80">
            <Line :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6 overflow-x-auto">
          <h3 class="text-lg font-bold text-gray-900 mb-4">Tableau Comparatif Détaillé</h3>
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b-2 border-gray-300">
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Métrique</th>
                <th 
                  v-for="result in comparisonResults.comparisons"
                  :key="result.id"
                  class="text-right py-3 px-4 font-semibold text-gray-700"
                >
                  {{ result.name }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="metric in tableMetrics" :key="metric.key" class="border-b border-gray-200 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-900">{{ metric.label }}</td>
                <td 
                  v-for="result in comparisonResults.comparisons"
                  :key="result.id"
                  class="text-right py-3 px-4 text-gray-900"
                >
                  {{ formatMetricValue(result.metrics[metric.key], metric.format) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!comparisonResults && !loading" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-12 text-center">
        <svg class="w-20 h-20 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Prêt à comparer ?</h3>
        <p class="text-gray-600">Sélectionnez 2 à 4 entités ci-dessus pour commencer la comparaison</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import Navbar from '@/components/Navbar.vue';
import ComparatorService from '@/services/comparatorService';
import { useToast } from '../composables/useToast';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const { toast } = useToast();

// State
const searchInput = ref(null);
const comparisonType = ref('pdv');
const period = ref('month');
const referenceDate = ref(new Date().toISOString().split('T')[0]);
const searchQuery = ref('');
const searchResults = ref([]);
const selectedEntities = ref([]);
const periodInputs = ref(['', '', '', '']);
const loading = ref(false);
const comparisonResults = ref(null);

// Computed
const selectedCount = computed(() => {
  if (comparisonType.value === 'period') {
    return periodInputs.value.filter(p => p.trim() !== '').length;
  }
  return selectedEntities.value.length;
});

const canCompare = computed(() => {
  return selectedCount.value >= 2 && selectedCount.value <= 4;
});

const tableMetrics = computed(() => {
  const base = [
    { key: 'ca', label: 'Chiffre d\'Affaires', format: 'currency' },
    { key: 'volume', label: 'Volume Total', format: 'currency' },
    { key: 'transactions', label: 'Transactions', format: 'number' },
  ];

  if (comparisonType.value === 'pdv') {
    base.push(
      { key: 'depots', label: 'Dépôts', format: 'number' },
      { key: 'retraits', label: 'Retraits', format: 'number' },
      { key: 'commission', label: 'Commission', format: 'currency' },
      { key: 'avg_transaction', label: 'Moy. Transaction', format: 'currency' }
    );
  } else if (comparisonType.value === 'dealer') {
    base.push(
      { key: 'commission', label: 'Commission', format: 'currency' },
      { key: 'ca_per_pdv', label: 'CA par PDV', format: 'currency' }
    );
  } else {
    base.push(
      { key: 'pdv_actifs', label: 'PDV Actifs', format: 'number' },
      { key: 'ca_per_day', label: 'CA par Jour', format: 'currency' }
    );
  }

  return base;
});

const chartData = computed(() => {
  if (!comparisonResults.value || !comparisonResults.value.comparisons || comparisonResults.value.comparisons.length === 0) {
    return null;
  }

  const colors = ['#FF6B00', '#3B82F6', '#10B981', '#F59E0B'];

  return {
    labels: comparisonResults.value.comparisons[0].evolution.map(e => e.date),
    datasets: comparisonResults.value.comparisons.map((result, index) => ({
      label: result.name,
      data: result.evolution.map(e => e.value),
      borderColor: colors[index],
      backgroundColor: colors[index] + '20',
      tension: 0.4,
      fill: false,
    })),
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'top',
    },
    tooltip: {
      callbacks: {
        label: (context) => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`,
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => formatCurrency(value),
      },
    },
  },
};

// Methods
const getEntityLabel = () => {
  return comparisonType.value === 'pdv' ? 'des PDV' : 
         comparisonType.value === 'dealer' ? 'des Dealers' : 
         'des périodes';
};

const resetSelection = () => {
  selectedEntities.value = [];
  searchResults.value = [];
  searchQuery.value = '';
  periodInputs.value = ['', '', '', ''];
  comparisonResults.value = null;
};

let searchTimeout = null;
const handleSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  
  if (searchQuery.value.length < 2) {
    searchResults.value = [];
    return;
  }

  searchTimeout = setTimeout(async () => {
    try {
      if (comparisonType.value === 'pdv') {
        const results = await ComparatorService.searchPdvs(searchQuery.value);
        searchResults.value = results.map(pdv => ({
          id: pdv.id,
          numero: pdv.numero,
          name: pdv.nom_point,
          subtitle: `${pdv.numero || 'N/A'} - ${pdv.organization?.name || 'Non attribué'}`,
        }));
      } else if (comparisonType.value === 'dealer') {
        const results = await ComparatorService.searchDealers(searchQuery.value);
        searchResults.value = results.map(dealer => ({
          id: dealer.id,
          name: dealer.name,
          subtitle: 'Dealer',
        }));
      }
    } catch (error) {
      console.error('Search error:', error);
    }
  }, 300);
};

const isSelected = (id) => {
  return selectedEntities.value.some(e => e.id === id);
};

const addEntity = (item) => {
  if (selectedEntities.value.length >= 4) {
    toast.warning('Maximum 4 entités pour la comparaison');
    return;
  }
  
  if (!isSelected(item.id)) {
    selectedEntities.value.push(item);
  }
  
  // Vider le champ de recherche et réinitialiser les résultats
  searchQuery.value = '';
  searchResults.value = [];
  
  // Refocus l'input pour permettre une nouvelle recherche
  nextTick(() => {
    if (searchInput.value) {
      searchInput.value.focus();
    }
  });
};

const removeEntity = (id) => {
  selectedEntities.value = selectedEntities.value.filter(e => e.id !== id);
};

const performComparison = async () => {
  if (!canCompare.value) return;

  loading.value = true;
  comparisonResults.value = null;

  try {
    let entities;
    
    if (comparisonType.value === 'period') {
      entities = periodInputs.value.filter(p => p.trim() !== '');
    } else {
      entities = selectedEntities.value.map(e => e.id);
    }

    let result;
    if (comparisonType.value === 'pdv') {
      result = await ComparatorService.comparePdvs(entities, period.value, referenceDate.value);
    } else if (comparisonType.value === 'dealer') {
      result = await ComparatorService.compareDealers(entities, period.value, referenceDate.value);
    } else {
      result = await ComparatorService.comparePeriods(entities, period.value, referenceDate.value);
    }
    
    comparisonResults.value = result;
  } catch (error) {
    console.error('Comparison error:', error);
    toast.error('Erreur lors de la comparaison');
  } finally {
    loading.value = false;
  }
};

const formatCurrency = (value) => {
  if (!value && value !== 0) return '0 FCFA';
  return new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
};

const formatNumber = (value) => {
  if (!value && value !== 0) return '0';
  return new Intl.NumberFormat('fr-FR').format(value);
};

const formatKey = (key) => {
  return key.replace(/_/g, ' ');
};

const formatMetricValue = (value, format) => {
  if (format === 'currency') return formatCurrency(value);
  if (format === 'number') return formatNumber(value);
  return value;
};

const getComparisonClass = (value, reference) => {
  const diff = ((value - reference) / reference) * 100;
  if (diff > 0) return 'text-green-600';
  if (diff < 0) return 'text-red-600';
  return 'text-gray-600';
};

const getComparisonText = (value, reference) => {
  const diff = ((value - reference) / reference) * 100;
  const sign = diff > 0 ? '+' : '';
  return `${sign}${diff.toFixed(1)}% vs référence`;
};

const exportToPDF = () => {
  if (!comparisonResults.value) return;

  try {
    const doc = new jsPDF();
    const pageWidth = doc.internal.pageSize.getWidth();
    let yPosition = 20;

    // En-tête
    doc.setFontSize(20);
    doc.setTextColor(255, 107, 0); // Moov Orange
    doc.text('Rapport de Comparaison', pageWidth / 2, yPosition, { align: 'center' });
    
    yPosition += 10;
    doc.setFontSize(10);
    doc.setTextColor(100, 100, 100);
    doc.text(`Généré le ${new Date().toLocaleString('fr-FR')}`, pageWidth / 2, yPosition, { align: 'center' });
    
    yPosition += 5;
    doc.text(
      `Période: ${comparisonResults.value.period.start} → ${comparisonResults.value.period.end}`,
      pageWidth / 2,
      yPosition,
      { align: 'center' }
    );

    yPosition += 15;

    // Type de comparaison
    doc.setFontSize(12);
    doc.setTextColor(0, 0, 0);
    const typeLabel = comparisonType.value === 'pdv' ? 'PDV' : 
                      comparisonType.value === 'dealer' ? 'Dealers' : 'Périodes';
    doc.text(`Type: Comparaison de ${typeLabel}`, 14, yPosition);

    yPosition += 10;

    // Entités comparées
    doc.setFontSize(10);
    doc.setTextColor(60, 60, 60);
    doc.text('Entités:', 14, yPosition);
    yPosition += 5;
    comparisonResults.value.comparisons.forEach((result, index) => {
      doc.text(`  ${index + 1}. ${result.name}`, 14, yPosition);
      yPosition += 5;
    });

    yPosition += 5;

    // Tableau des métriques principales
    const tableData = comparisonResults.value.comparisons.map((result) => [
      result.name,
      formatCurrency(result.metrics.ca),
      formatCurrency(result.metrics.volume),
      formatNumber(result.metrics.transactions),
    ]);

    autoTable(doc, {
      startY: yPosition,
      head: [['Entité', 'Chiffre d\'Affaires', 'Volume Total', 'Transactions']],
      body: tableData,
      theme: 'grid',
      headStyles: {
        fillColor: [255, 107, 0],
        textColor: [255, 255, 255],
        fontStyle: 'bold',
      },
      styles: {
        fontSize: 9,
        cellPadding: 3,
      },
      columnStyles: {
        0: { cellWidth: 50 },
        1: { cellWidth: 45, halign: 'right' },
        2: { cellWidth: 45, halign: 'right' },
        3: { cellWidth: 35, halign: 'right' },
      },
    });

    yPosition = doc.lastAutoTable.finalY + 15;

    // Tableau détaillé
    if (yPosition > 240) {
      doc.addPage();
      yPosition = 20;
    }

    doc.setFontSize(14);
    doc.setTextColor(255, 107, 0);
    doc.text('Métriques Détaillées', 14, yPosition);
    yPosition += 10;

    const detailedData = [];
    tableMetrics.value.forEach((metric) => {
      const row = [metric.label];
      comparisonResults.value.comparisons.forEach((result) => {
        row.push(formatMetricValue(result.metrics[metric.key], metric.format));
      });
      detailedData.push(row);
    });

    const detailedHeaders = ['Métrique'];
    comparisonResults.value.comparisons.forEach((result) => {
      detailedHeaders.push(result.name);
    });

    autoTable(doc, {
      startY: yPosition,
      head: [detailedHeaders],
      body: detailedData,
      theme: 'striped',
      headStyles: {
        fillColor: [255, 107, 0],
        textColor: [255, 255, 255],
        fontStyle: 'bold',
      },
      styles: {
        fontSize: 8,
        cellPadding: 2,
      },
      columnStyles: {
        0: { cellWidth: 45, fontStyle: 'bold' },
      },
    });

    yPosition = doc.lastAutoTable.finalY + 15;

    // Informations supplémentaires
    if (yPosition > 240) {
      doc.addPage();
      yPosition = 20;
    }

    doc.setFontSize(10);
    doc.setTextColor(100, 100, 100);
    comparisonResults.value.comparisons.forEach((result, index) => {
      if (yPosition > 270) {
        doc.addPage();
        yPosition = 20;
      }

      doc.setTextColor(255, 107, 0);
      doc.text(`${index + 1}. ${result.name}`, 14, yPosition);
      yPosition += 5;

      doc.setTextColor(60, 60, 60);
      if (result.info) {
        Object.entries(result.info).forEach(([key, value]) => {
          doc.text(`   ${formatKey(key)}: ${value}`, 14, yPosition);
          yPosition += 4;
        });
      }
      yPosition += 5;
    });

    // Pied de page
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
      doc.setPage(i);
      doc.setFontSize(8);
      doc.setTextColor(150, 150, 150);
      doc.text(
        `Page ${i} / ${pageCount}`,
        pageWidth / 2,
        doc.internal.pageSize.getHeight() - 10,
        { align: 'center' }
      );
      doc.text(
        'Moov Universe - Comparateur de Performance',
        pageWidth - 14,
        doc.internal.pageSize.getHeight() - 10,
        { align: 'right' }
      );
    }

    // Générer le nom du fichier
    const timestamp = new Date().toISOString().slice(0, 10);
    const filename = `comparaison_${comparisonType.value}_${timestamp}.pdf`;

    // Sauvegarder
    doc.save(filename);
    toast.success(`PDF exporté: ${filename}`);
  } catch (error) {
    console.error('Export PDF error:', error);
    toast.error('Erreur lors de l\'export PDF');
  }
};
</script>
