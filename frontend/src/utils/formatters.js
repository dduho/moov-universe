/**
 * Formate un numéro de téléphone au format 228 XX XX XX XX
 * @param {string} phone - Numéro de téléphone (11 chiffres)
 * @returns {string} - Numéro formaté
 */
export const formatPhone = (phone) => {
  if (!phone) return '';
  
  // Retirer tous les espaces et caractères non-numériques
  const cleaned = phone.toString().replace(/\D/g, '');
  
  // Si le numéro a 11 chiffres (228XXXXXXXX)
  if (cleaned.length === 11) {
    return `${cleaned.substring(0, 3)} ${cleaned.substring(3, 5)} ${cleaned.substring(5, 7)} ${cleaned.substring(7, 9)} ${cleaned.substring(9, 11)}`;
  }
  
  // Sinon retourner tel quel
  return phone;
};

/**
 * Formate un shortcode au format XXX XXXX
 * @param {string} shortcode - Shortcode (7 chiffres)
 * @returns {string} - Shortcode formaté
 */
export const formatShortcode = (shortcode) => {
  if (!shortcode) return '';
  
  // Retirer tous les espaces et caractères non-numériques
  const cleaned = shortcode.toString().replace(/\D/g, '');
  
  // Si le shortcode a 7 chiffres
  if (cleaned.length === 7) {
    return `${cleaned.substring(0, 3)} ${cleaned.substring(3, 7)}`;
  }
  
  // Sinon retourner tel quel
  return shortcode;
};
