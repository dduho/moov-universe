import api from './api';

/**
 * Service pour les prédictions et analyses de tendances
 */
export class PredictionService {
  
  /**
   * Obtenir les prédictions de performance pour les PDVs
   * @param {Object} params - Paramètres de prédiction
   * @param {number} params.forecast_days - Nombre de jours à prédire (défaut: 30)
   * @param {string} params.model_type - Type de modèle ('linear', 'seasonal', 'advanced')
   * @param {Array} params.pdv_ids - IDs spécifiques des PDVs (optionnel)
   * @param {string} params.region - Région spécifique (optionnel)
   * @returns {Promise<Object>} Prédictions avec tendances et alertes
   */
  static async getPredictions(params = {}) {
    try {
      const response = await api.post('/predictive-analytics/predictions', {
        forecast_days: params.forecast_days || 30,
        model_type: params.model_type || 'seasonal',
        pdv_ids: params.pdv_ids,
        region: params.region,
        confidence_level: params.confidence_level || 0.85
      });
      
      return {
        success: true,
        data: response.data.data,
        metadata: response.data.metadata || {}
      };
    } catch (error) {
      console.error('Erreur lors du chargement des prédictions:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors du chargement des prédictions',
        data: null
      };
    }
  }

  /**
   * Analyser les tendances saisonnières et cycliques
   * @param {Object} params - Paramètres d'analyse
   * @param {string} params.period - Période d'analyse ('monthly', 'weekly', 'yearly')
   * @param {Array} params.metrics - Métriques à analyser (['roi', 'ca', 'revenue'])
   * @returns {Promise<Object>} Analyse des tendances
   */
  static async getTrendAnalysis(params = {}) {
    try {
      const response = await api.post('/predictive-analytics/trends', {
        period: params.period || 'monthly',
        metrics: params.metrics || ['roi', 'ca', 'revenue'],
        start_date: params.start_date,
        end_date: params.end_date,
        group_by: params.group_by || 'pdv'
      });
      
      return {
        success: true,
        data: response.data.data,
        insights: response.data.insights || []
      };
    } catch (error) {
      console.error('Erreur lors de l\'analyse des tendances:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de l\'analyse des tendances',
        data: null
      };
    }
  }

  /**
   * Obtenir les alertes prédictives et recommandations
   * @param {Object} params - Paramètres des alertes
   * @param {number} params.threshold_roi - Seuil ROI pour alertes (défaut: 50%)
   * @param {number} params.threshold_decline - Seuil de baisse pour alerte (défaut: 20%)
   * @returns {Promise<Object>} Alertes et recommandations
   */
  static async getPredictiveAlerts(params = {}) {
    try {
      const response = await api.post('/predictive-analytics/alerts', {
        threshold_roi: params.threshold_roi || 50,
        threshold_decline: params.threshold_decline || 20,
        forecast_days: params.forecast_days || 14,
        alert_types: params.alert_types || ['performance_decline', 'low_roi', 'seasonal_anomaly']
      });
      
      return {
        success: true,
        data: response.data.data,
        alerts: response.data.alerts || [],
        recommendations: response.data.recommendations || []
      };
    } catch (error) {
      console.error('Erreur lors du chargement des alertes prédictives:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors du chargement des alertes',
        data: null
      };
    }
  }

  /**
   * Calculer les corrélations entre métriques pour insights
   * @param {Object} params - Paramètres de corrélation
   * @param {Array} params.metrics - Métriques à corréler
   * @param {string} params.timeframe - Période d'analyse
   * @returns {Promise<Object>} Matrice de corrélation et insights
   */
  static async getCorrelationAnalysis(params = {}) {
    try {
      const response = await api.post('/predictive-analytics/correlations', {
        metrics: params.metrics || ['roi', 'ca', 'revenue', 'margin_rate'],
        timeframe: params.timeframe || '90d',
        group_by: params.group_by || 'pdv'
      });
      
      return {
        success: true,
        data: response.data.data,
        insights: response.data.insights || []
      };
    } catch (error) {
      console.error('Erreur lors de l\'analyse de corrélation:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de l\'analyse de corrélation',
        data: null
      };
    }
  }

  /**
   * Obtenir les recommandations d'optimisation basées sur l'IA
   * @param {Object} params - Paramètres des recommandations
   * @param {string} params.pdv_id - ID du PDV spécifique (optionnel)
   * @param {string} params.optimization_type - Type d'optimisation ('roi', 'revenue', 'margin')
   * @returns {Promise<Object>} Recommandations personnalisées
   */
  static async getOptimizationRecommendations(params = {}) {
    try {
      const response = await api.post('/predictive-analytics/optimization-recommendations', {
        pdv_id: params.pdv_id,
        optimization_type: params.optimization_type || 'roi',
        analysis_depth: params.analysis_depth || 'standard',
        include_benchmarks: params.include_benchmarks !== false
      });
      
      return {
        success: true,
        data: response.data.data,
        recommendations: response.data.recommendations || [],
        benchmarks: response.data.benchmarks || {}
      };
    } catch (error) {
      console.error('Erreur lors du chargement des recommandations:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors du chargement des recommandations',
        data: null
      };
    }
  }

  /**
   * Simulation de scénarios "What-if" pour planification
   * @param {Object} scenario - Scénario à simuler
   * @param {Object} scenario.changes - Changements à appliquer
   * @param {number} scenario.forecast_days - Durée de simulation
   * @returns {Promise<Object>} Résultats de simulation
   */
  static async runScenarioSimulation(scenario) {
    try {
      const response = await api.post('/predictive-analytics/simulation', {
        changes: scenario.changes,
        forecast_days: scenario.forecast_days || 30,
        confidence_interval: scenario.confidence_interval || 0.95,
        simulation_type: scenario.type || 'conservative'
      });
      
      return {
        success: true,
        data: response.data.data,
        comparison: response.data.comparison || {},
        insights: response.data.insights || []
      };
    } catch (error) {
      console.error('Erreur lors de la simulation:', error);
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la simulation',
        data: null
      };
    }
  }

  /**
   * Utilitaire pour formater les prédictions pour les graphiques
   * @param {Object} predictions - Données de prédictions brutes
   * @returns {Object} Données formatées pour Chart.js
   */
  static formatPredictionsForChart(predictions) {
    if (!predictions || !predictions.forecasts) return null;

    const datasets = [];
    const labels = predictions.forecasts.map(f => new Date(f.date).toLocaleDateString('fr-FR'));

    // Dataset principal (données historiques + prédictions)
    datasets.push({
      label: 'ROI Prédit',
      data: predictions.forecasts.map(f => f.roi),
      borderColor: 'rgb(59, 130, 246)',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      fill: false,
      tension: 0.4,
      pointRadius: 2
    });

    // Intervalle de confiance
    if (predictions.confidence_intervals) {
      datasets.push({
        label: 'Intervalle de confiance',
        data: predictions.confidence_intervals.map(ci => ci.upper),
        borderColor: 'rgba(59, 130, 246, 0.3)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: '+1',
        pointRadius: 0
      });
      
      datasets.push({
        label: 'Limite basse',
        data: predictions.confidence_intervals.map(ci => ci.lower),
        borderColor: 'rgba(59, 130, 246, 0.3)',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        fill: false,
        pointRadius: 0
      });
    }

    return {
      labels,
      datasets
    };
  }
}

export default PredictionService;