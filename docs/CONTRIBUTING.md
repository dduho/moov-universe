# Guide de Contribution - Moov Money Universe

Merci de votre intérêt pour contribuer au projet Moov Money Universe!

## Table des Matières

1. [Code de Conduite](#code-de-conduite)
2. [Comment Contribuer](#comment-contribuer)
3. [Standards de Code](#standards-de-code)
4. [Structure du Projet](#structure-du-projet)
5. [Workflow Git](#workflow-git)
6. [Tests](#tests)

## Code de Conduite

Ce projet respecte un code de conduite professionnel. En participant, vous acceptez de maintenir un environnement respectueux et collaboratif.

## Comment Contribuer

### Signaler un Bug

1. Vérifiez que le bug n'a pas déjà été signalé dans les Issues
2. Créez une nouvelle Issue avec:
   - Un titre descriptif
   - Les étapes pour reproduire le bug
   - Le comportement attendu vs le comportement actuel
   - Captures d'écran si pertinent
   - Environnement (OS, navigateur, versions)

### Proposer une Fonctionnalité

1. Créez une Issue décrivant:
   - Le problème à résoudre
   - La solution proposée
   - Les alternatives envisagées
   - Impact sur le système existant

### Soumettre des Modifications

1. Forkez le repository
2. Créez une branche pour votre fonctionnalité (`feature/ma-fonctionnalite`)
3. Faites vos modifications
4. Testez localement
5. Committez avec des messages clairs
6. Poussez vers votre fork
7. Créez une Pull Request

## Standards de Code

### Backend (Laravel/PHP)

#### Style de Code

Suivre les [PSR-12](https://www.php-fig.org/psr/psr-12/) standards:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExampleController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        // Code here
    }
}
```

#### Conventions de Nommage

- **Classes**: PascalCase (`UserController`, `PointOfSale`)
- **Méthodes**: camelCase (`getUserData`, `validatePdv`)
- **Variables**: camelCase (`$userData`, `$pdvList`)
- **Constantes**: SCREAMING_SNAKE_CASE (`MAX_DISTANCE`, `DEFAULT_ROLE`)

#### Bonnes Pratiques

- Utiliser les type hints
- Documenter les méthodes publiques
- Valider toutes les entrées utilisateur
- Utiliser les Resource Classes pour les réponses API
- Garder les contrôleurs légers (logique métier dans les Services)

```php
/**
 * Validate a point of sale
 *
 * @param int $id PDV ID
 * @return \Illuminate\Http\JsonResponse
 */
public function validate(int $id): JsonResponse
{
    $pdv = PointOfSale::findOrFail($id);
    
    $this->validateService->execute($pdv);
    
    return response()->json($pdv->load('validator'));
}
```

### Frontend (Vue.js)

#### Style de Code

Suivre le [Vue Style Guide](https://vuejs.org/style-guide/):

```vue
<template>
  <div class="component-name">
    <h1>{{ title }}</h1>
    <button @click="handleClick">Action</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const title = ref('Title');

const handleClick = () => {
  // Handler
};
</script>

<style scoped>
.component-name {
  /* Styles */
}
</style>
```

#### Conventions de Nommage

- **Components**: PascalCase (`UserProfile.vue`, `PdvList.vue`)
- **Props**: camelCase
- **Events**: kebab-case (`@update-status`)
- **Variables**: camelCase
- **CSS Classes**: kebab-case

#### Bonnes Pratiques

- Composants réutilisables dans `/components`
- Vues de page dans `/views`
- Un composant = une responsabilité
- Utiliser Composition API avec `<script setup>`
- Props typées
- Emit events explicites

```vue
<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  pdv: {
    type: Object,
    required: true,
  },
  editable: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update', 'delete']);

const handleUpdate = () => {
  emit('update', props.pdv.id);
};
</script>
```

### CSS/Tailwind

#### Utiliser les Classes Tailwind

```vue
<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-4">Title</h2>
    <button class="bg-moov-orange hover:bg-moov-orange-dark text-white px-4 py-2 rounded">
      Action
    </button>
  </div>
</template>
```

#### Couleurs Moov Money

```vue
<!-- Utiliser les couleurs personnalisées -->
<div class="bg-moov-orange">Orange principal</div>
<div class="bg-moov-orange-light">Orange clair</div>
<div class="bg-moov-orange-dark">Orange foncé</div>
```

## Structure du Projet

### Backend

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/    # Un contrôleur par ressource
│   │   ├── Middleware/     # Middleware personnalisés
│   │   └── Requests/       # Form Requests pour validation
│   ├── Models/             # Modèles Eloquent
│   ├── Services/           # Logique métier
│   └── Repositories/       # Accès aux données (optionnel)
├── database/
│   ├── migrations/         # Migrations de DB
│   └── seeders/           # Données de test
├── routes/
│   └── api.php            # Routes API
└── tests/                 # Tests automatisés
```

### Frontend

```
frontend/
├── src/
│   ├── components/        # Composants réutilisables
│   ├── views/            # Pages/Vues
│   ├── stores/           # Stores Pinia
│   ├── services/         # API services
│   ├── router/           # Router config
│   ├── utils/            # Fonctions utilitaires
│   └── composables/      # Composables Vue
└── public/               # Assets statiques
```

## Workflow Git

### Branches

- `main`: Production
- `develop`: Développement
- `feature/*`: Nouvelles fonctionnalités
- `bugfix/*`: Corrections de bugs
- `hotfix/*`: Corrections urgentes

### Commits

Format des messages de commit:

```
type(scope): subject

body (optionnel)

footer (optionnel)
```

**Types:**
- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `docs`: Documentation
- `style`: Formatage, pas de changement de code
- `refactor`: Refactoring
- `test`: Ajout de tests
- `chore`: Maintenance

**Exemples:**

```
feat(pdv): add proximity alert feature

Implement GPS-based proximity alert system that warns when creating
a PDV within 300m of an existing validated PDV.

Refs: #123
```

```
fix(auth): correct token expiration handling

The token was not being properly refreshed on expiration.

Closes: #456
```

### Pull Requests

Template pour PR:

```markdown
## Description
[Description claire des changements]

## Type de changement
- [ ] Nouvelle fonctionnalité
- [ ] Correction de bug
- [ ] Amélioration de performance
- [ ] Refactoring
- [ ] Documentation

## Tests
- [ ] Tests unitaires ajoutés/mis à jour
- [ ] Tests manuels effectués
- [ ] Tous les tests passent

## Checklist
- [ ] Code suit les standards du projet
- [ ] Code documenté
- [ ] Pas de console.log/dd/dump
- [ ] Migrations testées
- [ ] Interface responsive testée
- [ ] Navigateurs testés (Chrome, Firefox, Safari)
```

## Tests

### Backend

```bash
# Exécuter tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter UserTest

# Coverage
php artisan test --coverage
```

#### Exemple de Test

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PointOfSale;

class PointOfSaleTest extends TestCase
{
    public function test_admin_can_validate_pdv()
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $pdv = PointOfSale::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->postJson("/api/point-of-sales/{$pdv->id}/validate");

        $response->assertOk();
        $this->assertEquals('validated', $pdv->fresh()->status);
    }
}
```

### Frontend

```bash
# Tests unitaires (à configurer)
npm run test:unit

# Tests e2e (à configurer)
npm run test:e2e
```

## Bonnes Pratiques Générales

### Sécurité

- ✅ Valider toutes les entrées
- ✅ Utiliser les requêtes préparées
- ✅ Protéger contre CSRF
- ✅ Sanitizer les sorties
- ✅ Ne jamais committer de secrets
- ✅ Utiliser HTTPS en production

### Performance

- ✅ Utiliser eager loading
- ✅ Indexer les colonnes recherchées
- ✅ Mettre en cache quand possible
- ✅ Optimiser les requêtes N+1
- ✅ Compresser les assets

### Accessibilité

- ✅ Utiliser des labels sur les inputs
- ✅ Alt text sur les images
- ✅ Navigation au clavier
- ✅ Contraste suffisant
- ✅ ARIA labels quand nécessaire

## Questions?

Si vous avez des questions:
1. Consultez la documentation
2. Recherchez dans les Issues
3. Créez une nouvelle Issue
4. Contactez l'équipe

Merci de contribuer à Moov Money Universe! 🚀
