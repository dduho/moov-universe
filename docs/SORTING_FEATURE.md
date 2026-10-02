# Amélioration du Tri - Liste des PDV

## Modifications Apportées

### 1. Nouveau champ de tri : "Dernière modification"

Ajout d'une nouvelle option dans le menu déroulant "Trier par" :
- Date de création (existant)
- **Dernière modification** (NOUVEAU) ← Tri par `updated_at`
- Nom
- Statut
- Région

### 2. Bouton de Basculement de l'Ordre de Tri

Un nouveau bouton a été ajouté à côté du sélecteur "Trier par" :

**Mode Descendant (défaut)** :
- Icône : Flèche vers le bas ↓
- Texte : "Plus récent"
- Ordre : Du plus récent au plus ancien (DESC)

**Mode Ascendant** :
- Icône : Flèche vers le haut ↑
- Texte : "Plus ancien"
- Ordre : Du plus ancien au plus récent (ASC)

### 3. Fonctionnalités

#### Basculement de l'ordre
- Cliquer sur le bouton inverse l'ordre de tri
- La liste est automatiquement rechargée avec le nouveau tri
- La page est réinitialisée à 1 lors du changement

#### Comportement avec les filtres
- L'ordre de tri est conservé lors de l'application d'autres filtres
- Le bouton "Réinitialiser les filtres" réinitialise aussi l'ordre à DESC
- L'ordre de tri est respecté lors de l'export (Excel/CSV)

## Placement dans l'Interface

Les deux contrôles de tri sont placés côte à côte dans la barre de filtres :

```
┌─────────────────────────────────────────────────────────┐
│ [Trier par ▼]  [🔽 Plus récent]                        │
└─────────────────────────────────────────────────────────┘
```

Lorsque l'utilisateur clique sur le bouton :

```
┌─────────────────────────────────────────────────────────┐
│ [Trier par ▼]  [🔼 Plus ancien]                        │
└─────────────────────────────────────────────────────────┘
```

## Exemples d'Utilisation

### Cas 1 : Voir les PDV récemment modifiés
1. Sélectionner "Dernière modification" dans "Trier par"
2. S'assurer que le bouton affiche "Plus récent" (DESC)
3. Les PDV modifiés le plus récemment apparaissent en premier

### Cas 2 : Voir les PDV les plus anciens non modifiés
1. Sélectionner "Dernière modification" dans "Trier par"
2. Cliquer sur le bouton pour passer à "Plus ancien" (ASC)
3. Les PDV les moins récemment modifiés apparaissent en premier

### Cas 3 : Tri alphabétique inversé
1. Sélectionner "Nom" dans "Trier par"
2. Cliquer sur le bouton pour passer à "Plus ancien" (ASC)
3. Les PDV sont triés de A à Z (au lieu de Z à A)

## Implémentation Technique

### Frontend (PointOfSaleList.vue)

#### État
```javascript
const filters = ref({
  // ... autres filtres
  sortBy: 'created_at',
  sortOrder: 'desc', // NOUVEAU
});
```

#### Fonction de basculement
```javascript
const toggleSortOrder = () => {
  filters.value.sortOrder = filters.value.sortOrder === 'desc' ? 'asc' : 'desc';
  currentPage.value = 1;
  fetchPointsOfSale();
};
```

#### Appel API
```javascript
const params = {
  page: currentPage.value,
  per_page: perPage.value,
  sort_by: filters.value.sortBy,
  sort_order: filters.value.sortOrder // Dynamique au lieu de 'desc' hardcodé
};
```

### Backend (Aucune modification requise)

Le backend `PointOfSaleController.php` supporte déjà :
- `updated_at` dans la liste des colonnes autorisées
- Les paramètres `sort_by` et `sort_order`
- Validation et sécurité SQL injection

## Tests Suggérés

1. ✅ Vérifier que le tri par "Date de création" fonctionne (ASC et DESC)
2. ✅ Vérifier que le tri par "Dernière modification" fonctionne (ASC et DESC)
3. ✅ Vérifier que le tri par "Nom" fonctionne (ASC et DESC)
4. ✅ Vérifier que le tri par "Statut" fonctionne (ASC et DESC)
5. ✅ Vérifier que le tri par "Région" fonctionne (ASC et DESC)
6. ✅ Vérifier que le bouton bascule correctement l'icône et le texte
7. ✅ Vérifier que la liste se rafraîchit après changement d'ordre
8. ✅ Vérifier que l'export respecte l'ordre de tri sélectionné
9. ✅ Vérifier que "Réinitialiser les filtres" réinitialise aussi l'ordre de tri

## Compatibilité

- ✅ Compatible avec tous les navigateurs modernes
- ✅ Responsive (mobile et desktop)
- ✅ Conserve les performances (pas de tri côté client)
- ✅ Fonctionne avec la pagination
- ✅ Fonctionne avec tous les autres filtres existants
