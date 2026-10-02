# Fonctionnalité de Verrouillage des PDV

## Vue d'ensemble

Cette fonctionnalité permet aux administrateurs de verrouiller des Points de Vente (PDV) pour empêcher qu'ils soient modifiés lors des imports de fichiers.

## Fonctionnement

### Backend

1. **Migration de base de données** (`2026_02_12_000001_add_is_locked_to_point_of_sales_table.php`)
   - Ajoute un champ `is_locked` (boolean, défaut: false) à la table `point_of_sales`
   - Position: après le champ `status`

2. **Modèle PointOfSale**
   - Champ `is_locked` ajouté aux `fillable`
   - Cast en boolean
   - Scopes ajoutés: `locked()` et `unlocked()`

3. **PointOfSaleController**
   - `POST /api/point-of-sales/{id}/lock` - Verrouille un PDV (admin uniquement)
   - `POST /api/point-of-sales/{id}/unlock` - Déverrouille un PDV (admin uniquement)

4. **PointOfSaleImportController**
   - **Preview**: Affiche le statut de verrouillage dans la prévisualisation
   - **Import**: 
     - Les PDV verrouillés sont automatiquement ignorés lors de l'import
     - Ajoutés à la liste `skipped` avec la raison "PDV verrouillé par un administrateur"
     - Ceci se produit indépendamment du paramètre `allow_updates`

### Frontend

1. **PointOfSaleService**
   - `lock(id)` - Appelle l'API pour verrouiller un PDV
   - `unlock(id)` - Appelle l'API pour déverrouiller un PDV

2. **PointOfSaleDetail.vue**
   - Bouton Verrouiller/Déverrouiller dans la section Actions (admin uniquement)
   - Badge "Verrouillé" affiché dans l'en-tête quand le PDV est verrouillé
   - Confirmation requise avant de verrouiller/déverrouiller

3. **PointOfSaleList.vue**
   - Icône de cadenas affichée à côté du statut pour les PDV verrouillés
   - Visible dans la vue tableau et dans la vue carte
   - Tooltip: "PDV verrouillé - ne sera pas modifié lors des imports"

## Utilisation

### Pour verrouiller un PDV

1. Accéder à la page de détails du PDV
2. Cliquer sur le bouton "Verrouiller" dans la section Actions
3. Confirmer l'action dans la boîte de dialogue
4. Le PDV est maintenant protégé contre les modifications lors des imports

### Pour déverrouiller un PDV

1. Accéder à la page de détails du PDV verrouillé
2. Cliquer sur le bouton "Déverrouiller" dans la section Actions
3. Confirmer l'action
4. Le PDV peut maintenant être mis à jour lors des imports

## Comportement lors de l'import

Lorsqu'un fichier d'import contient des données pour un PDV verrouillé:

1. **Prévisualisation**: Le PDV sera listé avec le message "VERROUILLÉ, ne sera pas mis à jour"
2. **Import réel**: Le PDV sera ignoré et ajouté à la liste des PDV sautés avec la raison appropriée
3. **Aucune donnée** du PDV verrouillé ne sera modifiée, garantissant l'intégrité des données saisies manuellement

## Permissions

- Seuls les **administrateurs** peuvent verrouiller/déverrouiller des PDV
- La vérification des permissions est effectuée à la fois côté backend et frontend
- Les utilisateurs non-admin ne verront pas les boutons de verrouillage

## Base de données

```sql
ALTER TABLE point_of_sales ADD COLUMN is_locked BOOLEAN DEFAULT FALSE AFTER status;
```

## API Endpoints

### Verrouiller un PDV
```
POST /api/point-of-sales/{id}/lock
Headers: Authorization: Bearer {token}
Permissions: admin

Response:
{
  "message": "PDV verrouillé avec succès",
  "data": { ... }
}
```

### Déverrouiller un PDV
```
POST /api/point-of-sales/{id}/unlock
Headers: Authorization: Bearer {token}
Permissions: admin

Response:
{
  "message": "PDV déverrouillé avec succès",
  "data": { ... }
}
```
