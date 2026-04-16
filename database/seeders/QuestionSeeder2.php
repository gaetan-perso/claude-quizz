<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\Seeder;

final class QuestionSeeder2 extends Seeder
{
    public function run(): void
    {
        $data = $this->questions();

        foreach ($data as $categorySlug => $questions) {
            $category = Category::where('slug', $categorySlug)->first();
            if (! $category) {
                $this->command->warn("Catégorie introuvable : {$categorySlug}");
                continue;
            }

            foreach ($questions as $q) {
                $question = Question::create([
                    'category_id'            => $category->id,
                    'text'                   => $q['text'],
                    'difficulty'             => Difficulty::from($q['difficulty']),
                    'type'                   => QuestionType::MultipleChoice,
                    'source'                 => QuestionSource::Manual,
                    'explanation'            => $q['explanation'],
                    'estimated_time_seconds' => $q['time'],
                    'tags'                   => [],
                    'is_active'              => true,
                ]);

                foreach ($q['choices'] as $order => $choice) {
                    Choice::create([
                        'question_id' => $question->id,
                        'text'        => $choice['text'],
                        'is_correct'  => $choice['correct'],
                        'order'       => $order,
                    ]);
                }
            }
        }

        $this->command->info('✅ Questions (lot 2) insérées en base.');
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function questions(): array
    {
        return [

            // ──────────────────────────────────────────────────────────
            // MATHÉMATIQUES
            // ──────────────────────────────────────────────────────────
            'mathematiques' => [

                // EASY ×20
                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le résultat de 12 × 12 ?",
                 'explanation'=>"12 × 12 = 144. C'est un carré parfait souvent mémorisé dans les tables de multiplication.",
                 'choices'=>[['text'=>'144','correct'=>true],['text'=>'124','correct'=>false],['text'=>'132','correct'=>false],['text'=>'148','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien vaut π (pi) approximativement ?",
                 'explanation'=>"Pi (π) vaut approximativement 3,14159... C'est le rapport entre la circonférence d'un cercle et son diamètre.",
                 'choices'=>[['text'=>'3,14','correct'=>true],['text'=>'3,41','correct'=>false],['text'=>'2,71','correct'=>false],['text'=>'1,61','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce qu'un nombre premier ?",
                 'explanation'=>"Un nombre premier est un entier naturel supérieur à 1 qui n'est divisible que par 1 et par lui-même.",
                 'choices'=>[['text'=>'Un entier divisible uniquement par 1 et lui-même','correct'=>true],['text'=>'Un entier pair supérieur à 2','correct'=>false],['text'=>'Un entier divisible par 3','correct'=>false],['text'=>'Un carré parfait','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle est la racine carrée de 144 ?",
                 'explanation'=>"La racine carrée de 144 est 12, car 12 × 12 = 144.",
                 'choices'=>[['text'=>'12','correct'=>true],['text'=>'14','correct'=>false],['text'=>'11','correct'=>false],['text'=>'13','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien de côtés a un hexagone ?",
                 'explanation'=>"Un hexagone a 6 côtés. Le préfixe 'hexa' vient du grec signifiant six.",
                 'choices'=>[['text'=>'6','correct'=>true],['text'=>'5','correct'=>false],['text'=>'7','correct'=>false],['text'=>'8','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le résultat de 2³ ?",
                 'explanation'=>"2³ signifie 2 × 2 × 2 = 8. La puissance indique combien de fois on multiplie la base par elle-même.",
                 'choices'=>[['text'=>'8','correct'=>true],['text'=>'6','correct'=>false],['text'=>'9','correct'=>false],['text'=>'12','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle est la somme des angles d'un triangle ?",
                 'explanation'=>"La somme des angles intérieurs d'un triangle est toujours égale à 180°, quelle que soit la forme du triangle.",
                 'choices'=>[['text'=>'180°','correct'=>true],['text'=>'360°','correct'=>false],['text'=>'90°','correct'=>false],['text'=>'270°','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le plus petit nombre premier ?",
                 'explanation'=>"Le plus petit nombre premier est 2. C'est aussi le seul nombre premier pair.",
                 'choices'=>[['text'=>'2','correct'=>true],['text'=>'1','correct'=>false],['text'=>'3','correct'=>false],['text'=>'0','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien vaut 5! (5 factorielle) ?",
                 'explanation'=>"5! = 5 × 4 × 3 × 2 × 1 = 120. La factorielle est le produit de tous les entiers de 1 jusqu'au nombre.",
                 'choices'=>[['text'=>'120','correct'=>true],['text'=>'60','correct'=>false],['text'=>'100','correct'=>false],['text'=>'25','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le résultat de 1000 ÷ 25 ?",
                 'explanation'=>"1000 ÷ 25 = 40. On peut aussi raisonner : 25 × 40 = 1000.",
                 'choices'=>[['text'=>'40','correct'=>true],['text'=>'35','correct'=>false],['text'=>'45','correct'=>false],['text'=>'50','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien de faces a un cube ?",
                 'explanation'=>"Un cube a 6 faces carrées de dimensions égales.",
                 'choices'=>[['text'=>'6','correct'=>true],['text'=>'4','correct'=>false],['text'=>'8','correct'=>false],['text'=>'12','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le carré de 9 ?",
                 'explanation'=>"9² = 9 × 9 = 81. Les carrés parfaits sont des entiers qui sont le carré d'un autre entier.",
                 'choices'=>[['text'=>'81','correct'=>true],['text'=>'72','correct'=>false],['text'=>'90','correct'=>false],['text'=>'18','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que le périmètre d'un carré de côté 5 ?",
                 'explanation'=>"Le périmètre d'un carré est 4 × côté = 4 × 5 = 20.",
                 'choices'=>[['text'=>'20','correct'=>true],['text'=>'25','correct'=>false],['text'=>'10','correct'=>false],['text'=>'15','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans le système décimal, combien de chiffres différents existent ?",
                 'explanation'=>"Le système décimal utilise 10 chiffres : 0, 1, 2, 3, 4, 5, 6, 7, 8 et 9.",
                 'choices'=>[['text'=>'10','correct'=>true],['text'=>'9','correct'=>false],['text'=>'8','correct'=>false],['text'=>'12','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le résultat de 7 × 8 ?",
                 'explanation'=>"7 × 8 = 56. C'est l'une des multiplications les plus fréquemment oubliées des tables.",
                 'choices'=>[['text'=>'56','correct'=>true],['text'=>'54','correct'=>false],['text'=>'48','correct'=>false],['text'=>'63','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel type de triangle a tous ses côtés de même longueur ?",
                 'explanation'=>"Un triangle équilatéral a ses trois côtés égaux et ses trois angles de 60° chacun.",
                 'choices'=>[['text'=>'Équilatéral','correct'=>true],['text'=>'Isocèle','correct'=>false],['text'=>'Scalène','correct'=>false],['text'=>'Rectangle','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que 50% de 200 ?",
                 'explanation'=>"50% de 200 = 200 × 0,5 = 100. 50% correspond à la moitié.",
                 'choices'=>[['text'=>'100','correct'=>true],['text'=>'50','correct'=>false],['text'=>'150','correct'=>false],['text'=>'25','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le successeur de 999 ?",
                 'explanation'=>"Le successeur de 999 est 1000, c'est-à-dire le nombre qui suit immédiatement.",
                 'choices'=>[['text'=>'1000','correct'=>true],['text'=>'998','correct'=>false],['text'=>'1001','correct'=>false],['text'=>'9990','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien font 3/4 + 1/4 ?",
                 'explanation'=>"3/4 + 1/4 = 4/4 = 1. Lorsque les dénominateurs sont identiques, on additionne les numérateurs.",
                 'choices'=>[['text'=>'1','correct'=>true],['text'=>'4/8','correct'=>false],['text'=>'2','correct'=>false],['text'=>'3/8','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel nombre est à la fois pair et premier ?",
                 'explanation'=>"2 est le seul nombre pair qui soit aussi premier. Tous les autres nombres pairs sont divisibles par 2.",
                 'choices'=>[['text'=>'2','correct'=>true],['text'=>'4','correct'=>false],['text'=>'6','correct'=>false],['text'=>'8','correct'=>false]]],

                // MEDIUM ×20
                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle est la formule de l'aire d'un cercle de rayon r ?",
                 'explanation'=>"L'aire d'un cercle est A = π × r². Par exemple, un cercle de rayon 3 a une aire de 9π ≈ 28,27.",
                 'choices'=>[['text'=>'π × r²','correct'=>true],['text'=>'2 × π × r','correct'=>false],['text'=>'π × d','correct'=>false],['text'=>'r²','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'énonce le théorème de Pythagore ?",
                 'explanation'=>"Dans un triangle rectangle, le carré de l'hypoténuse est égal à la somme des carrés des deux autres côtés : a² + b² = c².",
                 'choices'=>[['text'=>'a² + b² = c² dans un triangle rectangle','correct'=>true],['text'=>'a + b + c = 180°','correct'=>false],['text'=>'a × b = c²','correct'=>false],['text'=>'a² = b × c','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Combien vaut sin(90°) ?",
                 'explanation'=>"sin(90°) = 1. C'est la valeur maximale du sinus, atteinte pour un angle droit.",
                 'choices'=>[['text'=>'1','correct'=>true],['text'=>'0','correct'=>false],['text'=>'-1','correct'=>false],['text'=>'√2/2','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle est la dérivée de f(x) = x² ?",
                 'explanation'=>"La dérivée de x² est 2x. En général, la dérivée de xⁿ est n × x^(n-1).",
                 'choices'=>[['text'=>'2x','correct'=>true],['text'=>'x','correct'=>false],['text'=>'2','correct'=>false],['text'=>'x²','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle est la valeur de e (nombre d'Euler) approximativement ?",
                 'explanation'=>"Le nombre e ≈ 2,71828... C'est la base des logarithmes naturels et une constante fondamentale en mathématiques.",
                 'choices'=>[['text'=>'2,718','correct'=>true],['text'=>'3,141','correct'=>false],['text'=>'1,618','correct'=>false],['text'=>'2,302','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Combien vaut log₁₀(1000) ?",
                 'explanation'=>"log₁₀(1000) = 3 car 10³ = 1000. Le logarithme en base 10 donne la puissance à laquelle 10 doit être élevé.",
                 'choices'=>[['text'=>'3','correct'=>true],['text'=>'100','correct'=>false],['text'=>'10','correct'=>false],['text'=>'4','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quelle est la formule de la somme des n premiers entiers ?",
                 'explanation'=>"La somme 1 + 2 + ... + n = n(n+1)/2. Cette formule est attribuée au mathématicien Gauss.",
                 'choices'=>[['text'=>'n(n+1)/2','correct'=>true],['text'=>'n²/2','correct'=>false],['text'=>'n(n-1)/2','correct'=>false],['text'=>'n²','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le PGCD de 24 et 36 ?",
                 'explanation'=>"Le PGCD (Plus Grand Commun Diviseur) de 24 et 36 est 12. Les diviseurs communs sont 1, 2, 3, 4, 6 et 12.",
                 'choices'=>[['text'=>'12','correct'=>true],['text'=>'6','correct'=>false],['text'=>'8','correct'=>false],['text'=>'24','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Combien de solutions a au maximum une équation du second degré ?",
                 'explanation'=>"Une équation du second degré ax² + bx + c = 0 a au maximum 2 solutions réelles, selon le signe du discriminant Δ = b² - 4ac.",
                 'choices'=>[['text'=>'2','correct'=>true],['text'=>'1','correct'=>false],['text'=>'3','correct'=>false],['text'=>'Infini','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce qu'une suite arithmétique ?",
                 'explanation'=>"Une suite arithmétique est une suite dans laquelle chaque terme est obtenu en ajoutant une constante (la raison) au terme précédent. Ex : 2, 5, 8, 11... (raison 3).",
                 'choices'=>[['text'=>'Une suite où la différence entre deux termes consécutifs est constante','correct'=>true],['text'=>'Une suite où le rapport entre deux termes consécutifs est constant','correct'=>false],['text'=>'Une suite de nombres premiers','correct'=>false],['text'=>'Une suite de carrés parfaits','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le nombre d'or (φ) approximativement ?",
                 'explanation'=>"Le nombre d'or φ ≈ 1,618. Il vaut (1 + √5)/2 et apparaît dans de nombreux phénomènes naturels et artistiques.",
                 'choices'=>[['text'=>'1,618','correct'=>true],['text'=>'2,718','correct'=>false],['text'=>'3,141','correct'=>false],['text'=>'1,414','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quelle est la formule du volume d'une sphère de rayon r ?",
                 'explanation'=>"Le volume d'une sphère est V = (4/3) × π × r³.",
                 'choices'=>[['text'=>'(4/3) × π × r³','correct'=>true],['text'=>'π × r²','correct'=>false],['text'=>'4 × π × r²','correct'=>false],['text'=>'(2/3) × π × r³','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Combien vaut cos(0°) ?",
                 'explanation'=>"cos(0°) = 1. Le cosinus d'un angle nul vaut 1, c'est la valeur maximale du cosinus.",
                 'choices'=>[['text'=>'1','correct'=>true],['text'=>'0','correct'=>false],['text'=>'-1','correct'=>false],['text'=>'√2/2','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le discriminant Δ d'une équation ax² + bx + c = 0 ?",
                 'explanation'=>"Le discriminant est Δ = b² - 4ac. Si Δ > 0, deux solutions; si Δ = 0, une solution; si Δ < 0, pas de solution réelle.",
                 'choices'=>[['text'=>'b² - 4ac','correct'=>true],['text'=>'b² + 4ac','correct'=>false],['text'=>'-b / 2a','correct'=>false],['text'=>'4ac - b²','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce qu'une probabilité entre 0 et 1 représente ?",
                 'explanation'=>"Une probabilité de 0 signifie qu'un événement est impossible, 1 signifie qu'il est certain. Tout événement a une probabilité dans cet intervalle.",
                 'choices'=>[['text'=>'La vraisemblance qu\'un événement se produise','correct'=>true],['text'=>'Le nombre de fois qu\'un événement se produit','correct'=>false],['text'=>'La moyenne des résultats possibles','correct'=>false],['text'=>'L\'écart type des événements','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quelle est la dérivée de f(x) = sin(x) ?",
                 'explanation'=>"La dérivée de sin(x) est cos(x). C'est une des dérivées fondamentales à connaître en analyse.",
                 'choices'=>[['text'=>'cos(x)','correct'=>true],['text'=>'-cos(x)','correct'=>false],['text'=>'sin(x)','correct'=>false],['text'=>'-sin(x)','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le PPCM de 4 et 6 ?",
                 'explanation'=>"Le PPCM (Plus Petit Commun Multiple) de 4 et 6 est 12. C'est le plus petit entier divisible à la fois par 4 et par 6.",
                 'choices'=>[['text'=>'12','correct'=>true],['text'=>'24','correct'=>false],['text'=>'6','correct'=>false],['text'=>'18','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce qu'un vecteur en mathématiques ?",
                 'explanation'=>"Un vecteur est un objet mathématique défini par une direction, un sens et une norme (longueur). Il est représenté par une flèche.",
                 'choices'=>[['text'=>'Un objet avec direction, sens et norme','correct'=>true],['text'=>'Un point dans l\'espace','correct'=>false],['text'=>'Une équation à plusieurs variables','correct'=>false],['text'=>'Un nombre complexe','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le résultat de la somme d'une suite géométrique de premier terme 1 et de raison 1/2, avec 3 termes ?",
                 'explanation'=>"1 + 1/2 + 1/4 = 7/4 = 1,75. La somme de n termes d'une suite géométrique est a × (1 - rⁿ)/(1 - r).",
                 'choices'=>[['text'=>'1,75','correct'=>true],['text'=>'2','correct'=>false],['text'=>'1,5','correct'=>false],['text'=>'0,75','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Qu'est-ce que la médiane d'un ensemble de données ?",
                 'explanation'=>"La médiane est la valeur centrale qui partage l'ensemble de données ordonné en deux moitiés égales. Elle est moins sensible aux valeurs extrêmes que la moyenne.",
                 'choices'=>[['text'=>'La valeur centrale d\'un ensemble ordonné','correct'=>true],['text'=>'La valeur la plus fréquente','correct'=>false],['text'=>'La moyenne arithmétique','correct'=>false],['text'=>'La différence entre le max et le min','correct'=>false]]],

                // HARD ×20
                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que le dernier théorème de Fermat ?",
                 'explanation'=>"Le dernier théorème de Fermat stipule qu'il n'existe pas d'entiers positifs x, y, z tels que xⁿ + yⁿ = zⁿ pour n > 2. Prouvé par Andrew Wiles en 1995.",
                 'choices'=>[['text'=>'Aucun entier positif x, y, z ne vérifie xⁿ + yⁿ = zⁿ pour n > 2','correct'=>true],['text'=>'Tout nombre impair est premier','correct'=>false],['text'=>'Il existe une infinité de nombres premiers','correct'=>false],['text'=>'Tout entier est somme de quatre carrés','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la conjecture de Goldbach ?",
                 'explanation'=>"La conjecture de Goldbach (1742) affirme que tout entier pair supérieur à 2 est la somme de deux nombres premiers. Elle n'est pas encore prouvée.",
                 'choices'=>[['text'=>'Tout entier pair > 2 est somme de deux nombres premiers','correct'=>true],['text'=>'Tout nombre premier est impair','correct'=>false],['text'=>'Il existe une infinité de nombres premiers jumeaux','correct'=>false],['text'=>'Tout entier est produit de nombres premiers','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que l'identité d'Euler ?",
                 'explanation'=>"L'identité d'Euler est e^(iπ) + 1 = 0. Elle relie les cinq constantes mathématiques fondamentales : e, i, π, 1 et 0.",
                 'choices'=>[['text'=>'e^(iπ) + 1 = 0','correct'=>true],['text'=>'e^π = i','correct'=>false],['text'=>'e + iπ = 1','correct'=>false],['text'=>'e^i + π = 0','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le problème P = NP ?",
                 'explanation'=>"Le problème P vs NP demande si tout problème dont la solution peut être vérifiée rapidement peut aussi être résolu rapidement. C'est l'un des 7 problèmes du millénaire non résolus.",
                 'choices'=>[['text'=>'Les problèmes vérifiables rapidement sont-ils aussi résolubles rapidement ?','correct'=>true],['text'=>'Les nombres premiers sont-ils infinis ?','correct'=>false],['text'=>'Tout polynôme a-t-il une racine complexe ?','correct'=>false],['text'=>'L\'espace est-il à 3 dimensions ?','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce qu'un espace de Hilbert ?",
                 'explanation'=>"Un espace de Hilbert est un espace vectoriel muni d'un produit scalaire qui le rend complet (tout suite de Cauchy converge). Il généralise l'espace euclidien en dimension infinie.",
                 'choices'=>[['text'=>'Un espace vectoriel complet avec produit scalaire','correct'=>true],['text'=>'Un ensemble de nombres complexes','correct'=>false],['text'=>'Un groupe de transformations géométriques','correct'=>false],['text'=>'Un espace topologique non métrique','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qui a démontré l'impossibilité de trissecter un angle quelconque à la règle et au compas ?",
                 'explanation'=>"Pierre Wantzel a démontré en 1837 l'impossibilité de trissecter un angle quelconque avec règle et compas, en utilisant la théorie des corps algébriques.",
                 'choices'=>[['text'=>'Pierre Wantzel','correct'=>true],['text'=>'Carl Friedrich Gauss','correct'=>false],['text'=>'Évariste Galois','correct'=>false],['text'=>'Niels Abel','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la transformée de Fourier ?",
                 'explanation'=>"La transformée de Fourier décompose une fonction en une somme (ou intégrale) de fonctions sinusoïdales. Elle est fondamentale en traitement du signal et en physique.",
                 'choices'=>[['text'=>'Une décomposition d\'une fonction en fréquences sinusoïdales','correct'=>true],['text'=>'Une transformation géométrique par rotation','correct'=>false],['text'=>'Un algorithme de tri de signaux','correct'=>false],['text'=>'Une méthode de résolution d\'équations différentielles par substitution','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce qu'un nombre transcendant ?",
                 'explanation'=>"Un nombre transcendant est un nombre réel qui n'est racine d'aucun polynôme à coefficients entiers. π et e sont des exemples de nombres transcendants.",
                 'choices'=>[['text'=>'Un réel non racine d\'aucun polynôme à coefficients entiers','correct'=>true],['text'=>'Un nombre irrationnel quelconque','correct'=>false],['text'=>'Un nombre imaginaire pur','correct'=>false],['text'=>'Un nombre supérieur à tout entier','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le théorème d'incomplétude de Gödel ?",
                 'explanation'=>"Gödel (1931) a démontré que tout système formel cohérent suffisamment expressif contient des énoncés vrais mais indémontrables dans ce système.",
                 'choices'=>[['text'=>'Tout système formel cohérent contient des vérités indémontrables','correct'=>true],['text'=>'Tout théorème mathématique est prouvable','correct'=>false],['text'=>'Les mathématiques sont incohérentes','correct'=>false],['text'=>'Tout nombre est définissable algébriquement','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quelle est la dimension fractale du flocon de Koch ?",
                 'explanation'=>"La dimension fractale (de Hausdorff) du flocon de Koch est log(4)/log(3) ≈ 1,26. C'est une dimension non entière caractéristique des fractales.",
                 'choices'=>[['text'=>'log(4)/log(3) ≈ 1,26','correct'=>true],['text'=>'1,5','correct'=>false],['text'=>'2','correct'=>false],['text'=>'log(3)/log(2) ≈ 1,58','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la théorie des groupes en algèbre ?",
                 'explanation'=>"La théorie des groupes étudie les structures algébriques appelées groupes, définies par un ensemble muni d'une opération associative avec identité et inverses. Elle est fondamentale en physique et en cryptographie.",
                 'choices'=>[['text'=>'L\'étude des ensembles munis d\'une opération associative avec identité et inverses','correct'=>true],['text'=>'L\'étude des ensembles de nombres premiers','correct'=>false],['text'=>'L\'algèbre des matrices carrées uniquement','correct'=>false],['text'=>'L\'étude des suites et séries numériques','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel mathématicien a développé la géométrie non euclidienne hyperbolique ?",
                 'explanation'=>"Nikolaï Lobatchevski (et indépendamment János Bolyai) a développé la géométrie hyperbolique au XIXe siècle, remettant en cause le 5e postulat d'Euclide.",
                 'choices'=>[['text'=>'Nikolaï Lobatchevski','correct'=>true],['text'=>'Carl Friedrich Gauss','correct'=>false],['text'=>'Bernhard Riemann','correct'=>false],['text'=>'Euclide','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la conjecture de Riemann ?",
                 'explanation'=>"La conjecture de Riemann affirme que tous les zéros non triviaux de la fonction zêta de Riemann ont une partie réelle égale à 1/2. C'est l'un des problèmes du millénaire non résolus.",
                 'choices'=>[['text'=>'Les zéros non triviaux de ζ(s) ont Re(s) = 1/2','correct'=>true],['text'=>'Tout entier pair est somme de deux premiers','correct'=>false],['text'=>'La suite des premiers est aléatoire','correct'=>false],['text'=>'La fonction zêta est croissante','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce qu'une intégrale de Lebesgue ?",
                 'explanation'=>"L'intégrale de Lebesgue est une généralisation de l'intégrale de Riemann qui permet d'intégrer une classe plus large de fonctions, en mesurant des ensembles plutôt que des intervalles.",
                 'choices'=>[['text'=>'Une généralisation de l\'intégrale de Riemann basée sur la mesure','correct'=>true],['text'=>'Une somme infinie de termes décroissants','correct'=>false],['text'=>'La dérivée d\'une fonction complexe','correct'=>false],['text'=>'L\'intégrale d\'une fonction sur un cercle','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le théorème fondamental de l'algèbre ?",
                 'explanation'=>"Le théorème fondamental de l'algèbre stipule que tout polynôme non constant à coefficients complexes admet au moins une racine complexe. Démontré notamment par Gauss.",
                 'choices'=>[['text'=>'Tout polynôme non constant à coefficients complexes a une racine complexe','correct'=>true],['text'=>'Tout polynôme a ses racines entières','correct'=>false],['text'=>'Les polynômes de degré impair ont toujours une racine réelle','correct'=>false],['text'=>'La somme des racines est le coefficient dominant','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce qu'une variété différentielle ?",
                 'explanation'=>"Une variété différentielle est un espace topologique localement ressemblant à ℝⁿ, sur lequel on peut faire du calcul différentiel. Les sphères et les tores sont des exemples.",
                 'choices'=>[['text'=>'Un espace topologique localement homéomorphe à ℝⁿ, différentiable','correct'=>true],['text'=>'Un polynôme à plusieurs variables','correct'=>false],['text'=>'Une suite de fonctions différentiables','correct'=>false],['text'=>'Un espace vectoriel de dimension infinie','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quelle branche des mathématiques étudie les propriétés invariantes par déformation continue ?",
                 'explanation'=>"La topologie étudie les propriétés des espaces préservées par des transformations continues (homéomorphismes), sans rupture ni collage.",
                 'choices'=>[['text'=>'La topologie','correct'=>true],['text'=>'La géométrie algébrique','correct'=>false],['text'=>'L\'analyse fonctionnelle','correct'=>false],['text'=>'L\'algèbre commutative','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le théorème de Bayes ?",
                 'explanation'=>"Le théorème de Bayes permet de calculer la probabilité conditionnelle : P(A|B) = P(B|A) × P(A) / P(B). Il est fondamental en statistiques et en intelligence artificielle.",
                 'choices'=>[['text'=>'P(A|B) = P(B|A) × P(A) / P(B)','correct'=>true],['text'=>'P(A ∩ B) = P(A) + P(B)','correct'=>false],['text'=>'P(A|B) = P(A) × P(B)','correct'=>false],['text'=>'P(A ∪ B) = P(A) × P(B)','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la méthode des moindres carrés ?",
                 'explanation'=>"La méthode des moindres carrés minimise la somme des carrés des résidus (écarts entre valeurs observées et prédites). Elle est fondamentale en régression linéaire et statistiques.",
                 'choices'=>[['text'=>'Minimisation de la somme des carrés des résidus','correct'=>true],['text'=>'Calcul de la racine carrée de la variance','correct'=>false],['text'=>'Méthode de factorisation matricielle','correct'=>false],['text'=>'Calcul des valeurs propres d\'une matrice','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel est le nom du mathématicien qui a formalisé la théorie des ensembles ?",
                 'explanation'=>"Georg Cantor (1845-1918) est le fondateur de la théorie des ensembles. Il a aussi défini les nombres transfinis et démontré que certains infinis sont plus grands que d'autres.",
                 'choices'=>[['text'=>'Georg Cantor','correct'=>true],['text'=>'Giuseppe Peano','correct'=>false],['text'=>'Richard Dedekind','correct'=>false],['text'=>'David Hilbert','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // GASTRONOMIE
            // ──────────────────────────────────────────────────────────
            'gastronomie' => [

                // EASY ×20
                ['difficulty'=>'easy','time'=>15,'text'=>"Quel ingrédient principal compose le guacamole ?",
                 'explanation'=>"Le guacamole est une sauce mexicaine à base d'avocat écrasé, assaisonné de citron, oignon, coriandre et piment.",
                 'choices'=>[['text'=>'L\'avocat','correct'=>true],['text'=>'La tomate','correct'=>false],['text'=>'Le poivron','correct'=>false],['text'=>'Le concombre','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel pays est l'origine de la pizza ?",
                 'explanation'=>"La pizza est originaire d'Italie, plus précisément de Naples où la pizza Margherita a été créée au XIXe siècle.",
                 'choices'=>[['text'=>'L\'Italie','correct'=>true],['text'=>'La Grèce','correct'=>false],['text'=>'L\'Espagne','correct'=>false],['text'=>'La France','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"De quoi est principalement fait le fromage ?",
                 'explanation'=>"Le fromage est fabriqué à partir de lait (de vache, chèvre, brebis...) coagulé, égoutté et affiné.",
                 'choices'=>[['text'=>'Du lait','correct'=>true],['text'=>'De la crème fraîche','correct'=>false],['text'=>'Des œufs','correct'=>false],['text'=>'Du beurre','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est l'ingrédient principal des sushis ?",
                 'explanation'=>"Le riz vinaigré (riz à sushi) est l'ingrédient principal des sushis, accompagné de poisson cru, fruits de mer ou légumes.",
                 'choices'=>[['text'=>'Le riz vinaigré','correct'=>true],['text'=>'Le poisson cru','correct'=>false],['text'=>'Les algues','correct'=>false],['text'=>'Le wasabi','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel fruit est utilisé pour faire le vin ?",
                 'explanation'=>"Le vin est produit par la fermentation du jus de raisin. Les différentes variétés de raisin (cépages) donnent différents types de vins.",
                 'choices'=>[['text'=>'Le raisin','correct'=>true],['text'=>'La pomme','correct'=>false],['text'=>'La prune','correct'=>false],['text'=>'La poire','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que la baguette ?",
                 'explanation'=>"La baguette est un pain français long et fin, croustillant à l'extérieur et moelleux à l'intérieur. Elle est emblématique de la culture culinaire française.",
                 'choices'=>[['text'=>'Un pain français long et croustillant','correct'=>true],['text'=>'Une pâtisserie au chocolat','correct'=>false],['text'=>'Un ustensile de cuisine','correct'=>false],['text'=>'Un type de croissant','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le principal ingrédient du houmous ?",
                 'explanation'=>"Le houmous est une purée de pois chiches assaisonnée de tahini (pâte de sésame), citron, ail et huile d'olive. Originaire du Moyen-Orient.",
                 'choices'=>[['text'=>'Les pois chiches','correct'=>true],['text'=>'Les lentilles','correct'=>false],['text'=>'Le tofu','correct'=>false],['text'=>'Les haricots blancs','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel pays est réputé pour le chocolat de haute qualité ?",
                 'explanation'=>"La Suisse (et la Belgique) sont mondialement réputées pour la qualité de leur chocolat, avec des marques iconiques comme Lindt, Toblerone ou Godiva.",
                 'choices'=>[['text'=>'La Suisse','correct'=>true],['text'=>'L\'Allemagne','correct'=>false],['text'=>'La Suède','correct'=>false],['text'=>'Les Pays-Bas','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"De quelle viande est fait le jambon ?",
                 'explanation'=>"Le jambon est fabriqué à partir de cuisse de porc, salée et affinée (jambon cru) ou cuite (jambon blanc).",
                 'choices'=>[['text'=>'Du porc','correct'=>true],['text'=>'Du bœuf','correct'=>false],['text'=>'Du veau','correct'=>false],['text'=>'De l\'agneau','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que le thé vert ?",
                 'explanation'=>"Le thé vert est une infusion de feuilles de Camellia sinensis non oxydées. Originaire de Chine, il est riche en antioxydants.",
                 'choices'=>[['text'=>'Une infusion de feuilles de Camellia sinensis non fermentées','correct'=>true],['text'=>'Un jus de légumes verts','correct'=>false],['text'=>'Une tisane à la menthe','correct'=>false],['text'=>'Un extrait d\'algues marines','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel légume est la base de la ratatouille ?",
                 'explanation'=>"La ratatouille est un plat provençal composé principalement de courgettes, aubergines, poivrons et tomates mijotés à l'huile d'olive.",
                 'choices'=>[['text'=>'Courgettes, aubergines, poivrons et tomates','correct'=>true],['text'=>'Carottes, pommes de terre et oignons','correct'=>false],['text'=>'Haricots verts et champignons','correct'=>false],['text'=>'Poireaux et navets','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le plat national du Japon ?",
                 'explanation'=>"Le ramen (nouilles en bouillon) est considéré comme l'un des plats nationaux du Japon, bien que les sushis soient plus connus internationalement.",
                 'choices'=>[['text'=>'Le ramen','correct'=>true],['text'=>'Le pad thaï','correct'=>false],['text'=>'Le pho','correct'=>false],['text'=>'Le bibimbap','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que le beurre clarifié ?",
                 'explanation'=>"Le beurre clarifié (ou ghee) est du beurre fondu dont on a retiré l'eau et les protéines du lait, ne conservant que la matière grasse pure.",
                 'choices'=>[['text'=>'Du beurre dont on a retiré l\'eau et les protéines','correct'=>true],['text'=>'Du beurre mélangé à de la crème','correct'=>false],['text'=>'Du beurre allégé','correct'=>false],['text'=>'Du beurre salé fondu','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est l'épice la plus chère du monde ?",
                 'explanation'=>"Le safran est l'épice la plus chère du monde. Il est extrait des stigmates du Crocus sativus ; il faut environ 150 000 fleurs pour obtenir 1 kg de safran.",
                 'choices'=>[['text'=>'Le safran','correct'=>true],['text'=>'La vanille','correct'=>false],['text'=>'Le poivre noir','correct'=>false],['text'=>'La cannelle','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans quelle région française est produit le Camembert ?",
                 'explanation'=>"Le Camembert est produit en Normandie, dans le village de Camembert. Il bénéficie d'une appellation d'origine protégée (AOP).",
                 'choices'=>[['text'=>'La Normandie','correct'=>true],['text'=>'La Bretagne','correct'=>false],['text'=>'L\'Alsace','correct'=>false],['text'=>'La Bourgogne','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que le miso ?",
                 'explanation'=>"Le miso est une pâte fermentée japonaise à base de soja (et parfois d'orge ou de riz), utilisée pour les soupes et assaisonnements.",
                 'choices'=>[['text'=>'Une pâte fermentée japonaise à base de soja','correct'=>true],['text'=>'Une sauce soja douce','correct'=>false],['text'=>'Un riz gluant japonais','correct'=>false],['text'=>'Un fromage japonais','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel pays a inventé les croissants ?",
                 'explanation'=>"Contrairement à la croyance populaire, le croissant est originaire d'Autriche (Vienne), apporté en France par Marie-Antoinette au XVIIIe siècle.",
                 'choices'=>[['text'=>'L\'Autriche','correct'=>true],['text'=>'La France','correct'=>false],['text'=>'L\'Italie','correct'=>false],['text'=>'La Hongrie','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel vin est produit en Champagne ?",
                 'explanation'=>"La Champagne est une région viticole française produisant le champagne, un vin blanc pétillant élaboré selon la méthode champenoise.",
                 'choices'=>[['text'=>'Le champagne (vin pétillant)','correct'=>true],['text'=>'Le bordeaux','correct'=>false],['text'=>'Le bourgogne','correct'=>false],['text'=>'Le beaujolais','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que le tofu ?",
                 'explanation'=>"Le tofu est un aliment d'origine asiatique fabriqué à partir de lait de soja coagulé et pressé. Il est riche en protéines et souvent utilisé en cuisine végétarienne.",
                 'choices'=>[['text'=>'Du lait de soja coagulé et pressé','correct'=>true],['text'=>'Un fromage de chèvre asiatique','correct'=>false],['text'=>'Un légume fermenté','correct'=>false],['text'=>'Une pâte de haricots','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le principal ingrédient du pain traditionnel ?",
                 'explanation'=>"Le pain traditionnel est fait de farine (blé), eau, levure (ou levain) et sel. La farine de blé est l'ingrédient principal.",
                 'choices'=>[['text'=>'La farine de blé','correct'=>true],['text'=>'Le maïs','correct'=>false],['text'=>'Le seigle uniquement','correct'=>false],['text'=>'Le riz','correct'=>false]]],

                // MEDIUM ×20
                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que la réaction de Maillard ?",
                 'explanation'=>"La réaction de Maillard est une réaction chimique entre les acides aminés et les sucres réducteurs, responsable du brunissement des aliments lors de la cuisson (croûte du pain, viande grillée).",
                 'choices'=>[['text'=>'Une réaction entre acides aminés et sucres lors de la cuisson','correct'=>true],['text'=>'La caramélisation du sucre','correct'=>false],['text'=>'La fermentation alcoolique','correct'=>false],['text'=>'L\'émulsification des graisses','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce qu'un fond de sauce en cuisine ?",
                 'explanation'=>"Un fond est un bouillon concentré obtenu par cuisson longue d'os, de légumes et d'aromates. Il est la base de nombreuses sauces (fond brun, fond blanc, fumet de poisson).",
                 'choices'=>[['text'=>'Un bouillon concentré servant de base à une sauce','correct'=>true],['text'=>'Un épaississant pour les sauces','correct'=>false],['text'=>'Une sauce émulsifiée froide','correct'=>false],['text'=>'Un coulis de légumes','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel fromage français est caractérisé par ses moisissures bleues ?",
                 'explanation'=>"Le Roquefort est un fromage bleu au lait de brebis, produit dans la région de Roquefort-sur-Soulzon (Aveyron). Il est AOP depuis 1925.",
                 'choices'=>[['text'=>'Le Roquefort','correct'=>true],['text'=>'Le Brie','correct'=>false],['text'=>'Le Comté','correct'=>false],['text'=>'Le Cantal','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle technique culinaire consiste à cuire à basse température dans un emballage sous vide ?",
                 'explanation'=>"La cuisson sous vide (sous-vide) consiste à cuire des aliments emballés hermétiquement dans de l'eau à basse et précise température, préservant les jus et les arômes.",
                 'choices'=>[['text'=>'La cuisson sous vide','correct'=>true],['text'=>'La cuisson à l\'étouffée','correct'=>false],['text'=>'Le fumage à froid','correct'=>false],['text'=>'La cuisson papillote','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que l'umami ?",
                 'explanation'=>"L'umami est la cinquième saveur fondamentale (avec le sucré, salé, acide et amer), découverte par Kikunae Ikeda en 1908. Elle est associée au glutamate et caractérise les viandes, fromages et champignons.",
                 'choices'=>[['text'=>'La cinquième saveur fondamentale, associée au glutamate','correct'=>true],['text'=>'Une épice japonaise','correct'=>false],['text'=>'Un type de fermentation','correct'=>false],['text'=>'Une technique de présentation','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que la méthode de cuisson 'bain-marie' ?",
                 'explanation'=>"Le bain-marie consiste à placer un récipient dans un autre contenant de l'eau chaude. Cela permet une cuisson douce et homogène, idéale pour les crèmes, sauces délicates et chocolat fondu.",
                 'choices'=>[['text'=>'Cuire un aliment dans un récipient posé dans de l\'eau chaude','correct'=>true],['text'=>'Plonger les aliments dans l\'huile bouillante','correct'=>false],['text'=>'Cuire à la vapeur sous pression','correct'=>false],['text'=>'Faire mariner dans un liquide froid','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel cépage donne le Champagne Blanc de Blancs ?",
                 'explanation'=>"Le Champagne Blanc de Blancs est élaboré exclusivement à partir de Chardonnay, un cépage blanc. Il est élégant, floral et plus léger que les assemblages classiques.",
                 'choices'=>[['text'=>'Le Chardonnay uniquement','correct'=>true],['text'=>'Le Pinot Noir uniquement','correct'=>false],['text'=>'Le Pinot Meunier uniquement','correct'=>false],['text'=>'Un mélange de tous les cépages champenois','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Qu'est-ce que la fermentation lactique ?",
                 'explanation'=>"La fermentation lactique est une transformation du sucre en acide lactique par des bactéries. Elle est à l'origine du yaourt, du fromage, de la choucroute et du kimchi.",
                 'choices'=>[['text'=>'La transformation du sucre en acide lactique par des bactéries','correct'=>true],['text'=>'La fermentation du raisin en alcool','correct'=>false],['text'=>'La décomposition des graisses par des enzymes','correct'=>false],['text'=>'La transformation de l\'amidon en glucose','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le plat traditionnel de la région lyonnaise associé à Paul Bocuse ?",
                 'explanation'=>"La soupe aux truffes V.G.E. est le plat emblématique de Paul Bocuse, créée en 1975 pour l'Élysée. Il est aussi associé aux quenelles de brochet et à la volaille en vessie.",
                 'choices'=>[['text'=>'La soupe aux truffes','correct'=>true],['text'=>'La bouillabaisse','correct'=>false],['text'=>'Le cassoulet','correct'=>false],['text'=>'La choucroute','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le kimchi ?",
                 'explanation'=>"Le kimchi est un condiment coréen fermenté à base de chou (baechu) assaisonné de piment, ail, gingembre et sauce de poisson. Il est inscrit au patrimoine culturel immatériel de l'UNESCO.",
                 'choices'=>[['text'=>'Un chou fermenté épicé coréen','correct'=>true],['text'=>'Une sauce soja fermentée','correct'=>false],['text'=>'Un fromage fermenté asiatique','correct'=>false],['text'=>'Un riz vinaigré coréen','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle sauce est la base de la cuisine hollandaise ?",
                 'explanation'=>"La sauce hollandaise est une émulsion chaude à base de beurre clarifié, jaunes d'œufs et jus de citron. Elle accompagne classiquement les asperges et les œufs Bénédicte.",
                 'choices'=>[['text'=>'Beurre clarifié, jaunes d\'œufs et citron','correct'=>true],['text'=>'Crème fraîche, moutarde et vinaigre','correct'=>false],['text'=>'Tomates, ail et basilic','correct'=>false],['text'=>'Vin blanc, échalotes et crème','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le processus d'élaboration du vinaigre balsamique de Modène ?",
                 'explanation'=>"Le vinaigre balsamique traditionnel de Modène est produit à partir de moût de raisin cuit, fermenté puis affiné en fûts de bois de différentes essences pendant 12 à 25 ans minimum.",
                 'choices'=>[['text'=>'Moût de raisin cuit, fermenté et affiné en fûts de bois','correct'=>true],['text'=>'Vin rouge ordinaire fermenté','correct'=>false],['text'=>'Raisins séchés distillés','correct'=>false],['text'=>'Mélasse de canne à sucre fermentée','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Qu'est-ce que le carpaccio ?",
                 'explanation'=>"Le carpaccio est un plat d'origine italienne composé de fines tranches de viande (généralement de bœuf) ou de poisson cru, assaisonnées d'huile d'olive, citron et parmesan.",
                 'choices'=>[['text'=>'De fines tranches de viande ou poisson crus assaisonnés','correct'=>true],['text'=>'Un poisson fumé en tranches','correct'=>false],['text'=>'Une terrine de viande cuite','correct'=>false],['text'=>'Un steak tartare mélangé','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel pays est la patrie du curry ?",
                 'explanation'=>"Le curry (ou cari) est originaire du sous-continent indien. Le mot 'curry' vient du tamoul 'kari'. Il existe des centaines de variantes régionales à travers l'Inde.",
                 'choices'=>[['text'=>'L\'Inde','correct'=>true],['text'=>'La Thaïlande','correct'=>false],['text'=>'Le Sri Lanka','correct'=>false],['text'=>'Le Pakistan','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que la technique culinaire de l'émulsion ?",
                 'explanation'=>"L'émulsion est le mélange de deux liquides normalement non miscibles (eau et huile) grâce à un émulsifiant (jaune d'œuf, moutarde). La mayonnaise en est un exemple classique.",
                 'choices'=>[['text'=>'Le mélange stable d\'eau et d\'huile grâce à un émulsifiant','correct'=>true],['text'=>'La réduction d\'un liquide par évaporation','correct'=>false],['text'=>'Le passage d\'un aliment au tamis','correct'=>false],['text'=>'La cuisson dans un corps gras','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le temps de repos en boulangerie ?",
                 'explanation'=>"Le temps de repos (ou pointage, puis apprêt) permet au gluten de se détendre et à la levure de produire du CO₂, faisant gonfler la pâte. C'est essentiel pour la texture du pain.",
                 'choices'=>[['text'=>'La fermentation permettant à la pâte de lever','correct'=>true],['text'=>'Le temps de cuisson à basse température','correct'=>false],['text'=>'La durée de pétrissage','correct'=>false],['text'=>'Le refroidissement après cuisson','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle ville française est considérée comme la capitale mondiale de la gastronomie ?",
                 'explanation'=>"Lyon est souvent surnommée 'capitale de la gastronomie', grâce à ses bouchons lyonnais, ses chefs étoilés (Paul Bocuse) et sa riche tradition culinaire.",
                 'choices'=>[['text'=>'Lyon','correct'=>true],['text'=>'Paris','correct'=>false],['text'=>'Bordeaux','correct'=>false],['text'=>'Strasbourg','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Qu'est-ce que la fermentation alcoolique ?",
                 'explanation'=>"La fermentation alcoolique est la transformation du glucose en éthanol et CO₂ par des levures (principalement Saccharomyces cerevisiae). C'est le principe du vin, de la bière et des spiritueux.",
                 'choices'=>[['text'=>'La transformation du glucose en éthanol et CO₂ par des levures','correct'=>true],['text'=>'La transformation du sucre en acide lactique','correct'=>false],['text'=>'L\'oxydation de l\'alcool en vinaigre','correct'=>false],['text'=>'La décomposition des protéines en acides aminés','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le foie gras ?",
                 'explanation'=>"Le foie gras est un foie de canard ou d'oie engraissé par gavage. C'est une spécialité gastronomique française et une appellation protégée.",
                 'choices'=>[['text'=>'Un foie de canard ou d\'oie engraissé par gavage','correct'=>true],['text'=>'Un pâté de porc et volaille','correct'=>false],['text'=>'Un fromage à pâte molle','correct'=>false],['text'=>'Une terrine de légumes','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel pays a créé la paella ?",
                 'explanation'=>"La paella est originaire de la région de Valence, en Espagne. Elle est cuite dans une grande poêle plate (la 'paella') et contient traditionnellement du riz, safran, lapin et haricots.",
                 'choices'=>[['text'=>'L\'Espagne','correct'=>true],['text'=>'L\'Italie','correct'=>false],['text'=>'Le Portugal','correct'=>false],['text'=>'Le Mexique','correct'=>false]]],

                // HARD ×20
                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que la sphérification en cuisine moléculaire ?",
                 'explanation'=>"La sphérification est une technique de cuisine moléculaire d'Adrià consistant à encapsuler un liquide dans une membrane de gel (alginate + calcium), créant des sphères semblables à des œufs ou des perles.",
                 'choices'=>[['text'=>'Encapsuler un liquide dans une membrane de gel d\'alginate','correct'=>true],['text'=>'Transformer un liquide en mousse','correct'=>false],['text'=>'Congeler rapidement un aliment à l\'azote','correct'=>false],['text'=>'Créer une émulsion solide','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le kouign-amann ?",
                 'explanation'=>"Le kouign-amann (gâteau au beurre en breton) est une spécialité bretonne créée par Yves-René Scordia à Douarnenez en 1860. C'est un gâteau feuilleté à base de pâte levée, beurre et sucre caramélisé.",
                 'choices'=>[['text'=>'Un gâteau breton feuilleté au beurre et sucre caramélisé','correct'=>true],['text'=>'Un biscuit breton au blé noir','correct'=>false],['text'=>'Un pain d\'épices normand','correct'=>false],['text'=>'Une brioche alsacienne','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la méthode champenoise ?",
                 'explanation'=>"La méthode champenoise (ou méthode traditionnelle) consiste en une deuxième fermentation en bouteille, obtenant les bulles naturellement. Elle inclut le remuage, le dégorgement et le dosage.",
                 'choices'=>[['text'=>'Une deuxième fermentation en bouteille avec remuage et dégorgement','correct'=>true],['text'=>'Une carbonatation artificielle du vin','correct'=>false],['text'=>'Une fermentation en cuve close','correct'=>false],['text'=>'Un assemblage de vins de différentes années','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel est le nombre d'AOC/AOP fromagères françaises reconnues ?",
                 'explanation'=>"La France compte environ 46 fromages AOC/AOP (Appellation d'Origine Protégée), dont le Roquefort, le Comté, le Brie de Meaux ou le Camembert de Normandie.",
                 'choices'=>[['text'=>'Environ 46','correct'=>true],['text'=>'Environ 20','correct'=>false],['text'=>'Environ 100','correct'=>false],['text'=>'Environ 10','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que le dashi en cuisine japonaise ?",
                 'explanation'=>"Le dashi est un bouillon japonais fondamental à base de kombu (algue) et de katsuobushi (bonite séchée). C'est la base de la soupe miso, des ramen et de nombreuses sauces japonaises.",
                 'choices'=>[['text'=>'Un bouillon de kombu et bonite séchée','correct'=>true],['text'=>'Une pâte de miso diluée','correct'=>false],['text'=>'Un bouillon de poulet japonais','correct'=>false],['text'=>'Une sauce soja allongée à l\'eau','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le puntarelle en cuisine italienne ?",
                 'explanation'=>"Le puntarelle est une chicorée romaine (Cichorium intybus) dont les pousses intérieures sont servies en salade avec une vinaigrette aux anchois et à l'ail, plat traditionnel romain.",
                 'choices'=>[['text'=>'Une chicorée romaine servie en salade aux anchois','correct'=>true],['text'=>'Un type de pâtes courtes','correct'=>false],['text'=>'Un fromage de brebis sarde','correct'=>false],['text'=>'Un pain plat sicilien','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel chef français a popularisé la cuisine sous vide dans les années 1970 ?",
                 'explanation'=>"Georges Pralus et Bruno Goussault ont développé et popularisé la cuisson sous vide à basse température en France dans les années 1970, révolutionnant la cuisine professionnelle.",
                 'choices'=>[['text'=>'Georges Pralus et Bruno Goussault','correct'=>true],['text'=>'Paul Bocuse','correct'=>false],['text'=>'Joël Robuchon','correct'=>false],['text'=>'Ferran Adrià','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que l'agar-agar et d'où provient-il ?",
                 'explanation'=>"L'agar-agar est un gélifiant naturel extrait d'algues rouges (notamment Gelidium et Gracilaria). Il gélifie à température ambiante et peut remplacer la gélatine animale.",
                 'choices'=>[['text'=>'Un gélifiant extrait d\'algues rouges marines','correct'=>true],['text'=>'Une gomme extraite d\'acacia','correct'=>false],['text'=>'Un amidon de pomme de terre modifié','correct'=>false],['text'=>'Une protéine de soja gélifiante','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel grand cuisinier français est l'auteur du Guide Culinaire (1903) ?",
                 'explanation'=>"Auguste Escoffier a publié Le Guide Culinaire en 1903, ouvrage de référence qui a codifié la grande cuisine française et modernisé l'organisation des cuisines professionnelles.",
                 'choices'=>[['text'=>'Auguste Escoffier','correct'=>true],['text'=>'Antonin Carême','correct'=>false],['text'=>'Paul Bocuse','correct'=>false],['text'=>'Fernand Point','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le terroir en viticulture ?",
                 'explanation'=>"Le terroir désigne l'ensemble des facteurs naturels (sol, sous-sol, climat, exposition, topographie) et humains qui influencent le caractère d'un vin. C'est un concept central de la viticulture française.",
                 'choices'=>[['text'=>'L\'ensemble des facteurs naturels et humains influençant un vin','correct'=>true],['text'=>'La technique de taille de la vigne','correct'=>false],['text'=>'L\'assemblage de plusieurs cépages','correct'=>false],['text'=>'Le millésime d\'un vin','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la transglutaminase en cuisine moléculaire ?",
                 'explanation'=>"La transglutaminase est une enzyme permettant de coller des morceaux de protéines ensemble (viandes, poissons). Surnommée 'colle à viande', elle permet de créer des formes originales.",
                 'choices'=>[['text'=>'Une enzyme permettant de lier des protéines entre elles','correct'=>true],['text'=>'Un agent de texture végétal','correct'=>false],['text'=>'Un ferment pour les viandes','correct'=>false],['text'=>'Un colorant naturel pour les plats','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le noma et pourquoi est-il célèbre ?",
                 'explanation'=>"Noma est un restaurant de René Redzepi à Copenhague, plusieurs fois nommé meilleur restaurant du monde. Il a popularisé la cuisine nordique basée sur les produits locaux, fermentés et saisonniers.",
                 'choices'=>[['text'=>'Un restaurant de René Redzepi pionnier de la cuisine nordique','correct'=>true],['text'=>'Un restaurant espagnol de Ferran Adrià','correct'=>false],['text'=>'Un restaurant parisien de Pierre Gagnaire','correct'=>false],['text'=>'Un restaurant américain de Grant Achatz','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que l'acide glutamique en gastronomie ?",
                 'explanation'=>"L'acide glutamique (ou glutamate) est un acide aminé responsable de la saveur umami. Le glutamate monosodique (MSG) est son sel sodique, utilisé comme exhausteur de goût.",
                 'choices'=>[['text'=>'Un acide aminé responsable de la saveur umami','correct'=>true],['text'=>'Un conservateur alimentaire synthétique','correct'=>false],['text'=>'Un colorant naturel alimentaire','correct'=>false],['text'=>'Un sucre complexe épaississant','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le Bocuse d'Or ?",
                 'explanation'=>"Le Bocuse d'Or est un concours biennal de cuisine professionnelle créé par Paul Bocuse en 1987 à Lyon, considéré comme les 'Jeux Olympiques de la Gastronomie'.",
                 'choices'=>[['text'=>'Un concours international de cuisine professionnelle fondé par Bocuse','correct'=>true],['text'=>'Un guide de restaurants étoilés','correct'=>false],['text'=>'Un prix décerné aux meilleurs fromages français','correct'=>false],['text'=>'Un titre honorifique pour les MOF','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la macération à froid en vinification ?",
                 'explanation'=>"La macération à froid (cold soak) consiste à maintenir le moût entre 8 et 12°C pendant quelques jours avant la fermentation alcoolique, extrayant les arômes et la couleur sans tannins durs.",
                 'choices'=>[['text'=>'Contact moût-pellicules à basse température avant fermentation','correct'=>true],['text'=>'Refroidissement rapide du vin après fermentation','correct'=>false],['text'=>'Filtration du vin à très basse température','correct'=>false],['text'=>'Conservation du vin en cave froide','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel est le processus de fabrication du prosciutto di Parma ?",
                 'explanation'=>"Le prosciutto di Parma est un jambon cru fabriqué à partir de cuisse de porc salée à sec, séchée et affinée pendant au moins 12 mois dans la région de Parme, en Italie.",
                 'choices'=>[['text'=>'Cuisse de porc salée à sec et affinée 12 mois minimum','correct'=>true],['text'=>'Porc mariné dans le vin rouge puis fumé','correct'=>false],['text'=>'Porc cuit à basse température puis séché','correct'=>false],['text'=>'Cuisse de porc fumée puis saumurée','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que l'hydrolyse de l'amidon en boulangerie ?",
                 'explanation'=>"L'hydrolyse de l'amidon par les amylases (enzymes de la farine et de la levure) transforme l'amidon en sucres fermentescibles (maltose, glucose) qui nourrissent la levure et contribuent au brunissement.",
                 'choices'=>[['text'=>'La transformation de l\'amidon en sucres fermentescibles par des enzymes','correct'=>true],['text'=>'Le gonflement de l\'amidon à la chaleur','correct'=>false],['text'=>'La réticulation des protéines du gluten','correct'=>false],['text'=>'La dissolution du sel dans la pâte','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le troisième étoile Michelin représente ?",
                 'explanation'=>"La troisième étoile Michelin (introduite en 1933) signifie 'une cuisine d'exception qui vaut le voyage'. C'est la plus haute distinction du Guide Michelin, décernée à environ 130 restaurants dans le monde.",
                 'choices'=>[['text'=>'"Une cuisine d\'exception qui vaut le voyage"','correct'=>true],['text'=>'"Une très bonne cuisine, vaut le détour"','correct'=>false],['text'=>'"Une excellente cuisine"','correct'=>false],['text'=>'"Le meilleur restaurant du monde"','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la lacto-fermentation ?",
                 'explanation'=>"La lacto-fermentation est une fermentation anaérobie par des bactéries lactiques naturelles (Lactobacillus), transformant les sucres en acide lactique et conservant les aliments : cornichons, choucroute, kimchi.",
                 'choices'=>[['text'=>'Une fermentation anaérobie par bactéries lactiques naturelles','correct'=>true],['text'=>'Une fermentation avec ajout de lactose','correct'=>false],['text'=>'Une cuisson du lait sous vide','correct'=>false],['text'=>'Une fermentation alcoolique du lait','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel est le principe du 'nose-to-tail eating' ?",
                 'explanation'=>"Le 'nose-to-tail eating' (de Fergus Henderson) est une philosophie culinaire valorisant l'utilisation de toutes les parties de l'animal (abats, os, peau...), pour réduire le gaspillage et valoriser chaque morceau.",
                 'choices'=>[['text'=>'Utiliser toutes les parties de l\'animal pour éviter le gaspillage','correct'=>true],['text'=>'Manger uniquement des produits d\'origine animale','correct'=>false],['text'=>'Cuisiner chaque partie séparément','correct'=>false],['text'=>'Servir les plats dans l\'ordre anatomique','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // ART & PEINTURE
            // ──────────────────────────────────────────────────────────
            'art-peinture' => [

                // EASY ×20
                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a peint la Joconde ?",
                 'explanation'=>"La Joconde (La Gioconda) a été peinte par Léonard de Vinci entre 1503 et 1519. Elle est exposée au musée du Louvre à Paris.",
                 'choices'=>[['text'=>'Léonard de Vinci','correct'=>true],['text'=>'Michel-Ange','correct'=>false],['text'=>'Raphaël','correct'=>false],['text'=>'Botticelli','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans quel musée parisien est exposée la Joconde ?",
                 'explanation'=>"La Joconde est exposée au musée du Louvre, à Paris, l'un des plus grands musées du monde.",
                 'choices'=>[['text'=>'Le Louvre','correct'=>true],['text'=>'Le musée d\'Orsay','correct'=>false],['text'=>'Le Centre Pompidou','correct'=>false],['text'=>'Le Grand Palais','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a peint le Cri (1893) ?",
                 'explanation'=>"Le Cri a été peint par le peintre norvégien Edvard Munch en 1893. Il est l'une des œuvres les plus reconnaissables de l'histoire de l'art.",
                 'choices'=>[['text'=>'Edvard Munch','correct'=>true],['text'=>'Vincent van Gogh','correct'=>false],['text'=>'Paul Gauguin','correct'=>false],['text'=>'Henri Matisse','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel peintre est connu pour ses tournesols ?",
                 'explanation'=>"Vincent van Gogh est célèbre pour sa série de tableaux 'Les Tournesols', peints en 1888-1889 à Arles.",
                 'choices'=>[['text'=>'Vincent van Gogh','correct'=>true],['text'=>'Claude Monet','correct'=>false],['text'=>'Paul Cézanne','correct'=>false],['text'=>'Paul Gauguin','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel mouvement artistique est associé à Claude Monet ?",
                 'explanation'=>"Claude Monet est l'un des fondateurs de l'impressionnisme, courant artistique né en France dans les années 1860-1870.",
                 'choices'=>[['text'=>'L\'impressionnisme','correct'=>true],['text'=>'Le cubisme','correct'=>false],['text'=>'Le surréalisme','correct'=>false],['text'=>'Le romantisme','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a peint le plafond de la Chapelle Sixtine ?",
                 'explanation'=>"Michel-Ange a peint le plafond de la Chapelle Sixtine (1508-1512) à Rome, commandé par le pape Jules II. L'œuvre représente des scènes de la Genèse.",
                 'choices'=>[['text'=>'Michel-Ange','correct'=>true],['text'=>'Raphaël','correct'=>false],['text'=>'Léonard de Vinci','correct'=>false],['text'=>'Botticelli','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel artiste espagnol est connu pour le cubisme ?",
                 'explanation'=>"Pablo Picasso, artiste espagnol, est l'un des cofondateurs du cubisme avec Georges Braque, révolutionnant la représentation artistique au début du XXe siècle.",
                 'choices'=>[['text'=>'Pablo Picasso','correct'=>true],['text'=>'Salvador Dalí','correct'=>false],['text'=>'Joan Miró','correct'=>false],['text'=>'Francisco Goya','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce qu'une fresque ?",
                 'explanation'=>"Une fresque est une peinture murale réalisée sur un enduit frais (intonaco) avec des pigments dilués à l'eau. La technique est permanente car les pigments se lient chimiquement au support.",
                 'choices'=>[['text'=>'Une peinture murale sur enduit frais','correct'=>true],['text'=>'Une peinture sur toile à l\'huile','correct'=>false],['text'=>'Un dessin au fusain sur papier','correct'=>false],['text'=>'Une sculpture en bas-relief','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans quelle ville se trouve le musée du Prado ?",
                 'explanation'=>"Le musée du Prado est situé à Madrid, en Espagne. C'est l'un des plus importants musées du monde, abritant des œuvres de Vélasquez, Goya et El Greco.",
                 'choices'=>[['text'=>'Madrid','correct'=>true],['text'=>'Barcelone','correct'=>false],['text'=>'Séville','correct'=>false],['text'=>'Lisbonne','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a sculpté La Pietà exposée à Rome ?",
                 'explanation'=>"La Pietà est une sculpture de Michel-Ange réalisée entre 1498 et 1499, représentant la Vierge Marie tenant le corps du Christ. Elle est exposée dans la basilique Saint-Pierre au Vatican.",
                 'choices'=>[['text'=>'Michel-Ange','correct'=>true],['text'=>'Donatello','correct'=>false],['text'=>'Bernin','correct'=>false],['text'=>'Canova','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel courant artistique Salvador Dalí représente-t-il ?",
                 'explanation'=>"Salvador Dalí est l'un des représentants les plus célèbres du surréalisme, mouvement artistique explorant le monde des rêves et de l'inconscient.",
                 'choices'=>[['text'=>'Le surréalisme','correct'=>true],['text'=>'Le dadaïsme','correct'=>false],['text'=>'Le cubisme','correct'=>false],['text'=>'L\'expressionnisme','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce qu'une aquarelle ?",
                 'explanation'=>"L'aquarelle est une technique de peinture utilisant des pigments dilués dans l'eau, appliqués sur du papier spécial. Elle se caractérise par sa transparence et sa légèreté.",
                 'choices'=>[['text'=>'Une peinture aux pigments dilués dans l\'eau sur papier','correct'=>true],['text'=>'Une peinture à l\'huile sur bois','correct'=>false],['text'=>'Un dessin à l\'encre de Chine','correct'=>false],['text'=>'Une gravure sur cuivre','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel peintre français est connu pour ses nymphéas ?",
                 'explanation'=>"Claude Monet est connu pour sa série des Nymphéas (water lilies), peinte dans son jardin à Giverny entre 1896 et 1926. La grande série décorative est exposée à l'Orangerie à Paris.",
                 'choices'=>[['text'=>'Claude Monet','correct'=>true],['text'=>'Pierre-Auguste Renoir','correct'=>false],['text'=>'Edgar Degas','correct'=>false],['text'=>'Camille Pissarro','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel artiste a créé la sculpture 'Le Penseur' ?",
                 'explanation'=>"Le Penseur est une sculpture d'Auguste Rodin créée en 1881-1882. Elle représente un homme nu méditant, souvent interprété comme Dante contemplant les Enfers.",
                 'choices'=>[['text'=>'Auguste Rodin','correct'=>true],['text'=>'Aristide Maillol','correct'=>false],['text'=>'Antoine Bourdelle','correct'=>false],['text'=>'Camille Claudel','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que l'art abstrait ?",
                 'explanation'=>"L'art abstrait ne représente pas le monde visible de façon réaliste mais utilise formes, couleurs et lignes pour créer des compositions autonomes. Kandinsky en est un pionnier.",
                 'choices'=>[['text'=>'Un art qui ne représente pas la réalité figurative','correct'=>true],['text'=>'Un art inspiré de la nature','correct'=>false],['text'=>'Un art médiéval religieux','correct'=>false],['text'=>'Un art de la caricature','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans quel pays est né Léonard de Vinci ?",
                 'explanation'=>"Léonard de Vinci est né à Vinci, en Toscane (Italie), le 15 avril 1452. Il est l'incarnation de l'homme de la Renaissance.",
                 'choices'=>[['text'=>'En Italie','correct'=>true],['text'=>'En France','correct'=>false],['text'=>'En Espagne','correct'=>false],['text'=>'En Grèce','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le support traditionnel de la peinture à l'huile ?",
                 'explanation'=>"La peinture à l'huile est traditionnellement appliquée sur toile (préparée avec un enduit comme le gesso), bien qu'elle puisse aussi être peinte sur bois ou cuivre.",
                 'choices'=>[['text'=>'La toile','correct'=>true],['text'=>'Le papier','correct'=>false],['text'=>'La soie','correct'=>false],['text'=>'L\'ardoise','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel artiste américain est associé au Pop Art ?",
                 'explanation'=>"Andy Warhol est le figure emblématique du Pop Art américain, connu pour ses sérigraphies de Marilyn Monroe, de boîtes de soupe Campbell's et ses réflexions sur la société de consommation.",
                 'choices'=>[['text'=>'Andy Warhol','correct'=>true],['text'=>'Jackson Pollock','correct'=>false],['text'=>'Roy Lichtenstein','correct'=>false],['text'=>'Mark Rothko','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a peint La Nuit étoilée (1889) ?",
                 'explanation'=>"La Nuit étoilée a été peinte par Vincent van Gogh en juin 1889, alors qu'il était interné à l'asile de Saint-Paul-de-Mausole à Saint-Rémy-de-Provence.",
                 'choices'=>[['text'=>'Vincent van Gogh','correct'=>true],['text'=>'Paul Gauguin','correct'=>false],['text'=>'Henri Rousseau','correct'=>false],['text'=>'Georges Seurat','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qu'est-ce que la perspective en peinture ?",
                 'explanation'=>"La perspective est une technique permettant de représenter l'espace tridimensionnel sur une surface plane, en simulant la profondeur et la distance (perspective linéaire, aérienne).",
                 'choices'=>[['text'=>'Une technique pour représenter la profondeur sur une surface plane','correct'=>true],['text'=>'Un style de peinture sans ombre','correct'=>false],['text'=>'Une méthode de mélange des couleurs','correct'=>false],['text'=>'Un type de cadre pour tableau','correct'=>false]]],

                // MEDIUM ×20
                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le sfumato de Léonard de Vinci ?",
                 'explanation'=>"Le sfumato est une technique picturale de Léonard de Vinci consistant à estomper les contours et à fondre les transitions entre lumière et ombre, créant un effet vaporeux et mystérieux.",
                 'choices'=>[['text'=>'Une technique d\'estompage des contours créant un effet vaporeux','correct'=>true],['text'=>'Un type de vernis brillant','correct'=>false],['text'=>'Une technique de peinture en pointillés','correct'=>false],['text'=>'Un style de composition triangulaire','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel artiste a peint 'Las Meninas' (1656) ?",
                 'explanation'=>"Las Meninas a été peinte par Diego Vélasquez en 1656. Ce tableau complexe représente l'infante Marguerite-Thérèse entourée de ses suivantes, et est exposé au Prado.",
                 'choices'=>[['text'=>'Diego Vélasquez','correct'=>true],['text'=>'Francisco Goya','correct'=>false],['text'=>'El Greco','correct'=>false],['text'=>'Bartolomé Murillo','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le clair-obscur (chiaroscuro) en peinture ?",
                 'explanation'=>"Le clair-obscur est une technique utilisant les contrastes forts entre zones lumineuses et zones sombres pour créer du volume et du dramatisme. Caravage en est le maître.",
                 'choices'=>[['text'=>'Contraste fort entre lumière et ombre pour créer du volume','correct'=>true],['text'=>'Un style de peinture uniquement en noir et blanc','correct'=>false],['text'=>'L\'utilisation exclusive de couleurs claires','correct'=>false],['text'=>'La peinture à la lumière naturelle','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel mouvement artistique a émergé à Paris vers 1907 avec Picasso et Braque ?",
                 'explanation'=>"Le cubisme a émergé vers 1907-1908 avec 'Les Demoiselles d'Avignon' de Picasso et les paysages de Braque. Il déconstruit les formes en plans géométriques multiples.",
                 'choices'=>[['text'=>'Le cubisme','correct'=>true],['text'=>'Le fauvisme','correct'=>false],['text'=>'L\'expressionnisme','correct'=>false],['text'=>'Le futurisme','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui est l'auteur de 'La Persistance de la mémoire' (montres molles) ?",
                 'explanation'=>"'La Persistance de la mémoire' (1931) est une huile sur toile de Salvador Dalí, avec ses montres molles qui fondent dans un paysage surréaliste de Port Lligat.",
                 'choices'=>[['text'=>'Salvador Dalí','correct'=>true],['text'=>'René Magritte','correct'=>false],['text'=>'Max Ernst','correct'=>false],['text'=>'Giorgio de Chirico','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel peintre français est le père du pointillisme ?",
                 'explanation'=>"Georges Seurat est le créateur du pointillisme (ou divisionnisme), technique consistant à appliquer de petites touches de couleurs pures qui se mélangent optiquement dans l'œil du spectateur.",
                 'choices'=>[['text'=>'Georges Seurat','correct'=>true],['text'=>'Paul Signac','correct'=>false],['text'=>'Camille Pissarro','correct'=>false],['text'=>'Henri-Edmond Cross','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que l'Arte Povera ?",
                 'explanation'=>"L'Arte Povera est un mouvement artistique italien des années 1960-70 utilisant des matériaux 'pauvres' (bois, terre, vêtements, légumes) pour créer des œuvres conceptuelles. Figures : Jannis Kounellis, Mario Merz.",
                 'choices'=>[['text'=>'Un mouvement utilisant des matériaux pauvres et naturels','correct'=>true],['text'=>'Un mouvement d\'art de rue','correct'=>false],['text'=>'Un courant minimaliste américain','correct'=>false],['text'=>'Un style de peinture naïve','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le tableau le plus célèbre de Johannes Vermeer ?",
                 'explanation'=>"'La Jeune Fille à la perle' (vers 1665) est le tableau le plus célèbre de Vermeer, parfois surnommé 'la Joconde du Nord'. Il est exposé au musée Mauritshuis à La Haye.",
                 'choices'=>[['text'=>'La Jeune Fille à la perle','correct'=>true],['text'=>'L\'Atelier du peintre','correct'=>false],['text'=>'La Laitière','correct'=>false],['text'=>'La Ruelle','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quel artiste américain est le représentant de l'expressionnisme abstrait avec son action painting ?",
                 'explanation'=>"Jackson Pollock est le maître de l'action painting, technique consistant à drip-peindre (égoutter) la peinture sur une toile posée au sol, laissant une trace des gestes du peintre.",
                 'choices'=>[['text'=>'Jackson Pollock','correct'=>true],['text'=>'Mark Rothko','correct'=>false],['text'=>'Willem de Kooning','correct'=>false],['text'=>'Franz Kline','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que la lithographie ?",
                 'explanation'=>"La lithographie est une technique d'impression inventée par Senefelder (1796) basée sur la répulsion eau-matière grasse. Le dessin est tracé sur une pierre calcaire avec un crayon gras.",
                 'choices'=>[['text'=>'Une technique d\'impression sur pierre calcaire','correct'=>true],['text'=>'Une gravure sur métal à l\'acide','correct'=>false],['text'=>'Une impression par sérigraphie sur tissu','correct'=>false],['text'=>'Un tirage photographique en noir et blanc','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Dans quelle ville est situé le musée du Rijksmuseum et qui en est le peintre le plus célèbre ?",
                 'explanation'=>"Le Rijksmuseum est situé à Amsterdam (Pays-Bas). Il abrite les chefs-d'œuvre de Rembrandt van Rijn, dont La Ronde de nuit (1642), et de Vermeer.",
                 'choices'=>[['text'=>'Amsterdam, avec Rembrandt','correct'=>true],['text'=>'Rotterdam, avec Mondrian','correct'=>false],['text'=>'La Haye, avec Vermeer','correct'=>false],['text'=>'Utrecht, avec Bosch','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le trompe-l'œil ?",
                 'explanation'=>"Le trompe-l'œil est une technique picturale créant l'illusion de la réalité en 3D sur une surface plane. Il est utilisé en peinture murale, sur les plafonds baroque et dans les natures mortes.",
                 'choices'=>[['text'=>'Une technique créant une illusion de réalité 3D sur surface plane','correct'=>true],['text'=>'Un style de peinture impressionniste','correct'=>false],['text'=>'Une technique de sculpture en ronde-bosse','correct'=>false],['text'=>'Un type de cadre doré baroque','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui a peint 'Guernica' (1937) et pourquoi ?",
                 'explanation'=>"Guernica a été peinte par Pablo Picasso en 1937 en réaction au bombardement nazi de la ville basque de Guernica lors de la Guerre d'Espagne. C'est un symbole anti-guerre majeur.",
                 'choices'=>[['text'=>'Picasso, en réaction au bombardement de Guernica','correct'=>true],['text'=>'Dalí, pour dénoncer la révolution espagnole','correct'=>false],['text'=>'Miró, en hommage aux victimes','correct'=>false],['text'=>'Goya, lors de la guerre napoléonienne','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Qu'est-ce que le cloisonnisme en peinture ?",
                 'explanation'=>"Le cloisonnisme est une technique développée par Émile Bernard et Gauguin, utilisant des aplats de couleurs vives délimitées par des cernes noirs, s'inspirant des vitraux et de l'estampe japonaise.",
                 'choices'=>[['text'=>'Aplats de couleurs vives délimitées par des cernes noirs','correct'=>true],['text'=>'Un style de pointillisme coloré','correct'=>false],['text'=>'Une technique de dorure sur cadre','correct'=>false],['text'=>'L\'utilisation de couleurs en camaïeu','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel peintre mexicain est connu pour ses autoportraits et ses souffrances physiques ?",
                 'explanation'=>"Frida Kahlo est célèbre pour ses nombreux autoportraits expressifs, reflétant ses souffrances physiques (accident de bus, opérations) et émotionnelles. Elle est une icône de l'art mexicain.",
                 'choices'=>[['text'=>'Frida Kahlo','correct'=>true],['text'=>'Diego Rivera','correct'=>false],['text'=>'José Clemente Orozco','correct'=>false],['text'=>'David Alfaro Siqueiros','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le ready-made en art contemporain ?",
                 'explanation'=>"Le ready-made est un concept inventé par Marcel Duchamp : transformer un objet industriel banal en œuvre d'art par le simple geste de l'artiste de le déclarer tel. Ex. : 'Fontaine' (urinoir, 1917).",
                 'choices'=>[['text'=>'Un objet banal promu au rang d\'œuvre d\'art par déclaration artistique','correct'=>true],['text'=>'Une peinture réalisée rapidement sur le vif','correct'=>false],['text'=>'Un montage photographique','correct'=>false],['text'=>'Une sculpture assemblée de matériaux recyclés','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel est le style architectural caractéristique de la cathédrale de Notre-Dame de Paris ?",
                 'explanation'=>"Notre-Dame de Paris est un exemple emblématique de l'architecture gothique, caractérisée par ses arcs brisés, ses arcs-boutants, ses voûtes en ogive et ses grandes rosaces.",
                 'choices'=>[['text'=>'Le gothique','correct'=>true],['text'=>'Le roman','correct'=>false],['text'=>'Le baroque','correct'=>false],['text'=>'Le néoclassique','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quel artiste britannique est connu pour ses œuvres conceptuelles sur le temps et l'espace, comme les points colorés sur fond blanc ?",
                 'explanation'=>"Damien Hirst est célèbre pour ses spot paintings (séries de points colorés sur fond blanc) et ses œuvres controversées comme 'The Physical Impossibility of Death...' (requin dans le formol).",
                 'choices'=>[['text'=>'Damien Hirst','correct'=>true],['text'=>'Banksy','correct'=>false],['text'=>'Tracey Emin','correct'=>false],['text'=>'David Hockney','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qu'est-ce que le Bauhaus ?",
                 'explanation'=>"Le Bauhaus est une école allemande d'art et de design fondée par Walter Gropius en 1919 à Weimar. Elle a fusionné artisanat et beaux-arts, influençant profondément le design moderne.",
                 'choices'=>[['text'=>'Une école allemande alliant art et artisanat moderne (1919)','correct'=>true],['text'=>'Un mouvement surréaliste allemand','correct'=>false],['text'=>'Un style architectural baroque','correct'=>false],['text'=>'Un courant expressionniste allemand','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui est l'auteur des fresques de la Villa des Mystères à Pompéi ?",
                 'explanation'=>"Les fresques de la Villa des Mystères (vers 60 av. J.-C.) sont anonymes, mais représentent une des meilleures illustrations de la peinture romaine antique, probablement liées à des rituels dionysiaques.",
                 'choices'=>[['text'=>'Auteur inconnu, peintre romain anonyme','correct'=>true],['text'=>'Apelle de Colophon','correct'=>false],['text'=>'Zeuxis d\'Héraclée','correct'=>false],['text'=>'Polygnote de Thasos','correct'=>false]]],

                // HARD ×20
                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que le pentimento en peinture ?",
                 'explanation'=>"Le pentimento (repentir) désigne les changements de composition visibles sous la surface d'une peinture, révélés par radiographie ou avec le temps. Ils témoignent des hésitations de l'artiste.",
                 'choices'=>[['text'=>'Les repentirs visibles sous la surface d\'une peinture','correct'=>true],['text'=>'Une technique de restauration de tableaux','correct'=>false],['text'=>'Un procédé de vernissage ancien','correct'=>false],['text'=>'Une signature cachée du peintre','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le mouvement De Stijl et qui en est le fondateur ?",
                 'explanation'=>"De Stijl ('Le Style') est un mouvement néerlandais fondé par Theo van Doesburg et Piet Mondrian en 1917. Il prône l'abstraction pure avec grilles orthogonales et couleurs primaires.",
                 'choices'=>[['text'=>'Un mouvement néerlandais d\'abstraction géométrique fondé en 1917','correct'=>true],['text'=>'Un courant expressionniste allemand','correct'=>false],['text'=>'Un mouvement d\'art brut suisse','correct'=>false],['text'=>'Un style décoratif Art Nouveau','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que l'iconographie en histoire de l'art ?",
                 'explanation'=>"L'iconographie est l'étude du contenu thématique des œuvres d'art : identification des symboles, attributs, scènes et personnages représentés. Elle permet d'interpréter le sens d'une œuvre.",
                 'choices'=>[['text'=>'L\'étude du contenu thématique et symbolique des œuvres','correct'=>true],['text'=>'La technique de reproduction des icônes religieuses','correct'=>false],['text'=>'L\'analyse stylistique de la composition','correct'=>false],['text'=>'La datation des œuvres par analyse chimique','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qui était Giorgio Vasari et quelle est son œuvre majeure ?",
                 'explanation'=>"Giorgio Vasari (1511-1574) est un peintre et architecte florentin, auteur des 'Vies des meilleurs peintres, sculpteurs et architectes' (1550), première histoire de l'art moderne.",
                 'choices'=>[['text'=>'Un peintre florentin, auteur des Vies des artistes (1550)','correct'=>true],['text'=>'Un sculpteur vénitien du XVIe siècle','correct'=>false],['text'=>'Un architecte baroque romain','correct'=>false],['text'=>'Un théoricien flamand de la perspective','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le Jugendstil ?",
                 'explanation'=>"Le Jugendstil ('style de la jeunesse') est le nom allemand et austro-hongrois de l'Art Nouveau, courant ornemental de la fin du XIXe siècle caractérisé par des formes organiques inspirées de la nature.",
                 'choices'=>[['text'=>'Le nom allemand de l\'Art Nouveau à formes organiques','correct'=>true],['text'=>'Un mouvement expressionniste allemand','correct'=>false],['text'=>'Un style architectural baroque tardif','correct'=>false],['text'=>'Un courant d\'art abstrait viennois','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel peintre flamand est connu pour ses allégories du temps avec des vanités ?",
                 'explanation'=>"La vanité est un genre pictural flamand du XVIIe siècle représentant des crânes, bougies et objets symbolisant la fugacité de la vie. Pieter Claesz et Harmen Steenwijck en sont des maîtres.",
                 'choices'=>[['text'=>'Pieter Claesz','correct'=>true],['text'=>'Jan van Eyck','correct'=>false],['text'=>'Hieronymus Bosch','correct'=>false],['text'=>'Pieter Bruegel','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la technique de l'encaustique ?",
                 'explanation'=>"L'encaustique est une technique picturale antique utilisant des pigments mélangés à de la cire chauffée. Elle était utilisée par les Grecs et les Romains pour les portraits du Fayoum.",
                 'choices'=>[['text'=>'Peinture à la cire chauffée avec pigments, utilisée dans l\'Antiquité','correct'=>true],['text'=>'Peinture à l\'œuf (tempera)','correct'=>false],['text'=>'Fresque humide sur enduit frais','correct'=>false],['text'=>'Peinture à l\'huile de lin','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel est le titre exact de l'œuvre de Magritte représentant une pipe avec l'inscription 'Ceci n'est pas une pipe' ?",
                 'explanation'=>"'La Trahison des images' (1929) est le tableau de René Magritte représentant une pipe avec la légende 'Ceci n'est pas une pipe', questionnant la relation entre représentation et réalité.",
                 'choices'=>[['text'=>'La Trahison des images','correct'=>true],['text'=>'L\'Évidence éternelle','correct'=>false],['text'=>'Le Sens propre','correct'=>false],['text'=>'La Condition humaine','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le maniérisme en peinture ?",
                 'explanation'=>"Le maniérisme est un courant artistique européen (1520-1600) caractérisé par des figures allongées, des compositions complexes et artificieuses, et un raffinement extrême. Il suit la Haute Renaissance.",
                 'choices'=>[['text'=>'Un style post-Renaissance aux figures allongées et compositions complexes','correct'=>true],['text'=>'Un courant d\'imitation naive des maîtres','correct'=>false],['text'=>'Un style baroque tardif','correct'=>false],['text'=>'Un mouvement minimaliste du XVIe siècle','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel est le nom de la technique de Seurat basée sur la juxtaposition de petites touches de couleur pure ?",
                 'explanation'=>"Le pointillisme ou divisionnisme est la technique de Seurat (et Signac) consistant à juxtaposer de petites touches de couleurs pures qui se mélangent optiquement dans l'œil du spectateur.",
                 'choices'=>[['text'=>'Le divisionnisme (ou pointillisme)','correct'=>true],['text'=>'Le cloisonnisme','correct'=>false],['text'=>'L\'impasto','correct'=>false],['text'=>'Le sfumato','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le concept d'œuvre d'art totale (Gesamtkunstwerk) ?",
                 'explanation'=>"Le Gesamtkunstwerk ('œuvre d'art totale') est un concept de Richard Wagner désignant une œuvre fusionnant toutes les disciplines artistiques (musique, théâtre, poésie, scénographie) en un tout cohérent.",
                 'choices'=>[['text'=>'Une œuvre fusionnant toutes les disciplines artistiques, concept de Wagner','correct'=>true],['text'=>'Un tableau combinant toutes les techniques picturales','correct'=>false],['text'=>'Un musée présentant toutes les formes d\'art','correct'=>false],['text'=>'Une installation artistique monumentale','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel peintre du XVe siècle est célèbre pour son 'Jardin des délices' ?",
                 'explanation'=>"Hieronymus Bosch a peint 'Le Jardin des délices' (vers 1500-1510), triptyque représentant le paradis, la vie terrestre et l'enfer, avec ses créatures fantastiques et symboliques.",
                 'choices'=>[['text'=>'Hieronymus Bosch','correct'=>true],['text'=>'Lucas Cranach l\'Ancien','correct'=>false],['text'=>'Pieter Bruegel l\'Ancien','correct'=>false],['text'=>'Hans Holbein','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la provenance d'une œuvre d'art ?",
                 'explanation'=>"La provenance est l'historique de propriété d'une œuvre d'art depuis sa création. Elle est cruciale pour authentifier les œuvres, détecter les faux et restituer les œuvres spoliées pendant la Seconde Guerre mondiale.",
                 'choices'=>[['text'=>'L\'historique de propriété d\'une œuvre depuis sa création','correct'=>true],['text'=>'Le lieu de création de l\'œuvre','correct'=>false],['text'=>'La technique utilisée par l\'artiste','correct'=>false],['text'=>'Le certificat d\'authenticité actuel','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel mouvement artistique du début du XXe siècle rejette toute rationalité et prône le non-sens ?",
                 'explanation'=>"Le dadaïsme, fondé à Zurich en 1916 au Cabaret Voltaire par Hugo Ball et Tristan Tzara, rejette la logique, la raison et l'esthétique en réaction à l'horreur de la Première Guerre mondiale.",
                 'choices'=>[['text'=>'Le dadaïsme','correct'=>true],['text'=>'Le futurisme','correct'=>false],['text'=>'Le surréalisme','correct'=>false],['text'=>'Le constructivisme','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qu'est-ce que la technique de la détrempe (tempera) ?",
                 'explanation'=>"La tempera (détrempe) est une technique picturale utilisant des pigments liés à l'œuf (généralement le jaune), au lait ou à la colle animale. Utilisée avant la peinture à l'huile, elle sèche vite et est permanente.",
                 'choices'=>[['text'=>'Peinture aux pigments liés à l\'œuf ou à la colle','correct'=>true],['text'=>'Peinture diluée à l\'essence de térébenthine','correct'=>false],['text'=>'Peinture à l\'eau sur soie','correct'=>false],['text'=>'Peinture acrylique en couches épaisses','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le colorfield painting ?",
                 'explanation'=>"Le colorfield painting est un courant de l'expressionnisme abstrait américain (années 1950-60) caractérisé par de grandes surfaces de couleur uniforme créant une expérience émotionnelle immersive. Mark Rothko en est le maître.",
                 'choices'=>[['text'=>'Un courant abstrait américain de grandes surfaces de couleur uniforme','correct'=>true],['text'=>'Un mouvement de peinture en plein air','correct'=>false],['text'=>'Un style de peinture géométrique hard-edge','correct'=>false],['text'=>'Un courant fauve aux couleurs vives','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que la conservation-restauration d'une œuvre d'art ?",
                 'explanation'=>"La conservation-restauration est la discipline qui préserve (conservation préventive) et restaure les œuvres d'art dégradées pour les transmettre aux générations futures, en respectant l'authenticité matérielle.",
                 'choices'=>[['text'=>'La préservation et restauration des œuvres pour les générations futures','correct'=>true],['text'=>'La reproduction fidèle d\'une œuvre endommagée','correct'=>false],['text'=>'L\'exposition des œuvres dans des conditions climatiques contrôlées','correct'=>false],['text'=>'L\'authentification des œuvres par des experts','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qui a créé le concept d'installation artistique immersive ?",
                 'explanation'=>"L'installation comme forme artistique s'est développée dans les années 1960-70. Allan Kaprow (happenings), puis des artistes comme Yayoi Kusama ou James Turrell sont pionniers de l'art immersif.",
                 'choices'=>[['text'=>'Allan Kaprow, avec les happenings dans les années 1960','correct'=>true],['text'=>'Marcel Duchamp, avec ses ready-mades','correct'=>false],['text'=>'Andy Warhol, avec la Factory','correct'=>false],['text'=>'Joseph Beuys, avec ses actions sociales','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce que le sublime en esthétique philosophique ?",
                 'explanation'=>"Le sublime (Edmund Burke, 1757 ; Kant, 1790) désigne une expérience esthétique face à ce qui dépasse la beauté ordinaire : une nature grandiose et terrifiante qui suscite émerveillement et effroi mêlés.",
                 'choices'=>[['text'=>'Une expérience d\'émerveillement mêlé d\'effroi face à une grandeur dépassante','correct'=>true],['text'=>'Le degré suprême de la beauté classique','correct'=>false],['text'=>'Un style de peinture romantique très détaillé','correct'=>false],['text'=>'L\'expression de la perfection formelle','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel artiste japonais est célèbre pour sa série 'Trente-six vues du mont Fuji' ?",
                 'explanation'=>"Katsushika Hokusai (1760-1849) est l'auteur des 'Trente-six vues du mont Fuji' (1831-1833), dont la célèbre estampe 'La Grande Vague de Kanagawa'. Il a profondément influencé les impressionnistes.",
                 'choices'=>[['text'=>'Hokusai','correct'=>true],['text'=>'Hiroshige','correct'=>false],['text'=>'Utamaro','correct'=>false],['text'=>'Harunobu','correct'=>false]]],
            ],
        ];
    }
}
