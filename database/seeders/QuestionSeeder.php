<?php declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Choice;
use App\Models\Question;
use Illuminate\Database\Seeder;

final class QuestionSeeder extends Seeder
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

        $this->command->info('✅ Questions insérées en base.');
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function questions(): array
    {
        return [

            // ──────────────────────────────────────────────────────────
            // HISTOIRE
            // ──────────────────────────────────────────────────────────
            'histoire' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"En quelle année a eu lieu la Révolution française ?",
                 'explanation'=>"La Révolution française débute en 1789 avec la prise de la Bastille le 14 juillet.",
                 'choices'=>[['text'=>'1789','correct'=>true],['text'=>'1776','correct'=>false],['text'=>'1804','correct'=>false],['text'=>'1815','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui était Napoléon Bonaparte ?",
                 'explanation'=>"Napoléon Bonaparte fut général, Premier consul puis Empereur des Français de 1804 à 1815.",
                 'choices'=>[['text'=>'Empereur des Français','correct'=>true],['text'=>'Roi de France','correct'=>false],['text'=>'Général anglais','correct'=>false],['text'=>'Président de la République','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle guerre a opposé la France et la Prusse en 1870 ?",
                 'explanation'=>"La guerre franco-prussienne de 1870-1871 s'est soldée par la défaite française et la proclamation de l'Empire allemand.",
                 'choices'=>[['text'=>'La guerre franco-prussienne','correct'=>true],['text'=>'La guerre de Cent Ans','correct'=>false],['text'=>'La guerre de Crimée','correct'=>false],['text'=>'La guerre des Boers','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel traité a mis fin à la Première Guerre mondiale ?",
                 'explanation'=>"Le traité de Versailles, signé le 28 juin 1919, a officiellement mis fin à la Première Guerre mondiale.",
                 'choices'=>[['text'=>'Le traité de Versailles','correct'=>true],['text'=>'Le traité de Paris','correct'=>false],['text'=>'Le traité de Westphalie','correct'=>false],['text'=>'Le traité de Berlin','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"En quelle année tombe le mur de Berlin ?",
                 'explanation'=>"Le mur de Berlin tombe dans la nuit du 9 au 10 novembre 1989, marquant la fin de la Guerre froide.",
                 'choices'=>[['text'=>'1989','correct'=>true],['text'=>'1991','correct'=>false],['text'=>'1985','correct'=>false],['text'=>'1993','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui a dirigé l'Allemagne nazie de 1933 à 1945 ?",
                 'explanation'=>"Adolf Hitler a dirigé l'Allemagne nazie en tant que Führer de 1933 jusqu'à sa mort le 30 avril 1945.",
                 'choices'=>[['text'=>'Adolf Hitler','correct'=>true],['text'=>'Heinrich Himmler','correct'=>false],['text'=>'Hermann Göring','correct'=>false],['text'=>'Joseph Goebbels','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle bataille de 1515 est une victoire célèbre de François Ier ?",
                 'explanation'=>"La bataille de Marignan (1515) est une victoire éclatante de François Ier contre les Suisses en Italie.",
                 'choices'=>[['text'=>'Marignan','correct'=>true],['text'=>'Azincourt','correct'=>false],['text'=>'Poitiers','correct'=>false],['text'=>'Bouvines','correct'=>false]]],

                ['difficulty'=>'hard','time'=>45,'text'=>"Quel événement a déclenché la Première Guerre mondiale en 1914 ?",
                 'explanation'=>"L'assassinat de l'archiduc François-Ferdinand d'Autriche à Sarajevo le 28 juin 1914 a déclenché la Première Guerre mondiale.",
                 'choices'=>[['text'=>"L'assassinat de l'archiduc François-Ferdinand",'correct'=>true],['text'=>"L'invasion de la Belgique par l'Allemagne",'correct'=>false],['text'=>"La révolution russe",'correct'=>false],['text'=>"L'attentat de Vienne",'correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Qui était le pharaon lors de la construction de la grande pyramide de Gizeh ?",
                 'explanation'=>"La grande pyramide de Gizeh a été construite vers 2560 av. J.-C. sous le règne du pharaon Khéops (ou Chéops).",
                 'choices'=>[['text'=>'Khéops','correct'=>true],['text'=>'Ramsès II','correct'=>false],['text'=>'Toutânkhamon','correct'=>false],['text'=>'Aménophis IV','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"En quelle année a été fondée la République romaine ?",
                 'explanation'=>"La République romaine a été fondée en 509 av. J.-C. après l'expulsion du dernier roi étrusque Tarquin le Superbe.",
                 'choices'=>[['text'=>'509 av. J.-C.','correct'=>true],['text'=>'264 av. J.-C.','correct'=>false],['text'=>'753 av. J.-C.','correct'=>false],['text'=>'44 av. J.-C.','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // GÉOGRAPHIE
            // ──────────────────────────────────────────────────────────
            'geographie' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle est la capitale de l'Australie ?",
                 'explanation'=>"La capitale de l'Australie est Canberra, souvent confondue avec Sydney qui est la plus grande ville.",
                 'choices'=>[['text'=>'Canberra','correct'=>true],['text'=>'Sydney','correct'=>false],['text'=>'Melbourne','correct'=>false],['text'=>'Brisbane','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel est le plus long fleuve du monde ?",
                 'explanation'=>"Le Nil, avec environ 6 650 km, est généralement considéré comme le plus long fleuve du monde.",
                 'choices'=>[['text'=>'Le Nil','correct'=>true],['text'=>"L'Amazone",'correct'=>false],['text'=>'Le Yangtsé','correct'=>false],['text'=>'Le Mississippi','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Sur quel continent se trouve le Sahara ?",
                 'explanation'=>"Le Sahara est le plus grand désert chaud du monde, situé en Afrique du Nord.",
                 'choices'=>[['text'=>'Afrique','correct'=>true],['text'=>'Asie','correct'=>false],['text'=>'Amérique du Sud','correct'=>false],['text'=>'Australie','correct'=>false]]],

                ['difficulty'=>'medium','time'=>25,'text'=>"Quelle est la capitale du Canada ?",
                 'explanation'=>"Ottawa est la capitale du Canada, souvent confondue avec Toronto ou Montréal qui sont les plus grandes villes.",
                 'choices'=>[['text'=>'Ottawa','correct'=>true],['text'=>'Toronto','correct'=>false],['text'=>'Montréal','correct'=>false],['text'=>'Vancouver','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel océan est le plus grand du monde ?",
                 'explanation'=>"L'océan Pacifique est le plus grand et le plus profond des océans, couvrant plus d'un tiers de la surface terrestre.",
                 'choices'=>[['text'=>"L'océan Pacifique",'correct'=>true],['text'=>"L'océan Atlantique",'correct'=>false],['text'=>"L'océan Indien",'correct'=>false],['text'=>"L'océan Arctique",'correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Dans quel pays se trouve le mont Everest ?",
                 'explanation'=>"Le mont Everest se trouve à la frontière entre le Népal et le Tibet (Chine), dans la chaîne de l'Himalaya.",
                 'choices'=>[['text'=>'Népal et Tibet','correct'=>true],['text'=>'Inde et Pakistan','correct'=>false],['text'=>'Afghanistan','correct'=>false],['text'=>'Bhoutan','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Combien de pays composent l'Union européenne ?",
                 'explanation'=>"Depuis le Brexit en 2020, l'Union européenne est composée de 27 États membres.",
                 'choices'=>[['text'=>'27','correct'=>true],['text'=>'28','correct'=>false],['text'=>'25','correct'=>false],['text'=>'30','correct'=>false]]],

                ['difficulty'=>'hard','time'=>45,'text'=>"Quel est le pays le plus petit du monde ?",
                 'explanation'=>"Le Vatican (Saint-Siège) est le pays le plus petit du monde avec une superficie de seulement 0,44 km².",
                 'choices'=>[['text'=>'Vatican','correct'=>true],['text'=>'Monaco','correct'=>false],['text'=>'Saint-Marin','correct'=>false],['text'=>'Liechtenstein','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quelle est la profondeur maximale de la fosse des Mariannes ?",
                 'explanation'=>"La fosse des Mariannes, dans l'océan Pacifique, atteint environ 11 034 mètres au point Challenger Deep.",
                 'choices'=>[['text'=>'11 034 mètres','correct'=>true],['text'=>'8 848 mètres','correct'=>false],['text'=>'9 500 mètres','correct'=>false],['text'=>'12 500 mètres','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quelle ville est surnommée 'La Perle de l'Orient' ?",
                 'explanation'=>"Hong Kong est souvent appelée 'La Perle de l'Orient' en raison de son importance commerciale et financière en Asie.",
                 'choices'=>[['text'=>'Hong Kong','correct'=>true],['text'=>'Shanghai','correct'=>false],['text'=>'Singapour','correct'=>false],['text'=>'Tokyo','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // SCIENCES
            // ──────────────────────────────────────────────────────────
            'sciences' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle planète est la plus proche du Soleil ?",
                 'explanation'=>"Mercure est la planète la plus proche du Soleil dans notre système solaire.",
                 'choices'=>[['text'=>'Mercure','correct'=>true],['text'=>'Vénus','correct'=>false],['text'=>'Mars','correct'=>false],['text'=>'Terre','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle est la formule chimique de l'eau ?",
                 'explanation'=>"L'eau est composée de deux atomes d'hydrogène et d'un atome d'oxygène, d'où la formule H₂O.",
                 'choices'=>[['text'=>'H₂O','correct'=>true],['text'=>'CO₂','correct'=>false],['text'=>'O₂','correct'=>false],['text'=>'H₂O₂','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien de chromosomes possède une cellule humaine normale ?",
                 'explanation'=>"Une cellule humaine normale possède 46 chromosomes, organisés en 23 paires.",
                 'choices'=>[['text'=>'46','correct'=>true],['text'=>'23','correct'=>false],['text'=>'48','correct'=>false],['text'=>'92','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui a formulé la théorie de la relativité générale ?",
                 'explanation'=>"Albert Einstein a publié sa théorie de la relativité générale en 1915, révolutionnant notre compréhension de la gravité.",
                 'choices'=>[['text'=>'Albert Einstein','correct'=>true],['text'=>'Isaac Newton','correct'=>false],['text'=>'Max Planck','correct'=>false],['text'=>'Niels Bohr','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle est la vitesse de la lumière dans le vide ?",
                 'explanation'=>"La vitesse de la lumière dans le vide est d'environ 299 792 458 m/s, soit approximativement 300 000 km/s.",
                 'choices'=>[['text'=>'300 000 km/s','correct'=>true],['text'=>'150 000 km/s','correct'=>false],['text'=>'450 000 km/s','correct'=>false],['text'=>'3 000 km/s','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel gaz est le plus abondant dans l'atmosphère terrestre ?",
                 'explanation'=>"L'azote (N₂) représente environ 78% de l'atmosphère terrestre, suivi de l'oxygène à 21%.",
                 'choices'=>[['text'=>"L'azote",'correct'=>true],['text'=>"L'oxygène",'correct'=>false],['text'=>"Le dioxyde de carbone",'correct'=>false],['text'=>"L'argon",'correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quelle est la particule élémentaire qui compose les protons et neutrons ?",
                 'explanation'=>"Les protons et les neutrons sont composés de quarks, des particules élémentaires du modèle standard de la physique.",
                 'choices'=>[['text'=>'Le quark','correct'=>true],['text'=>"L'électron",'correct'=>false],['text'=>'Le photon','correct'=>false],['text'=>'Le neutrino','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quelle loi décrit la relation entre la pression et le volume d'un gaz à température constante ?",
                 'explanation'=>"La loi de Boyle-Mariotte stipule que la pression et le volume d'un gaz sont inversement proportionnels à température constante (P×V = constante).",
                 'choices'=>[['text'=>'La loi de Boyle-Mariotte','correct'=>true],['text'=>'La loi de Charles','correct'=>false],['text'=>'La loi de Gay-Lussac','correct'=>false],['text'=>'La loi de Dalton','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel est le numéro atomique de l'or ?",
                 'explanation'=>"L'or (Au) a le numéro atomique 79, ce qui signifie qu'il possède 79 protons dans son noyau.",
                 'choices'=>[['text'=>'79','correct'=>true],['text'=>'47','correct'=>false],['text'=>'82','correct'=>false],['text'=>'29','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Que mesure l'échelle de Richter ?",
                 'explanation'=>"L'échelle de Richter mesure la magnitude des séismes, c'est-à-dire l'énergie libérée lors d'un tremblement de terre.",
                 'choices'=>[['text'=>"La magnitude des séismes",'correct'=>true],['text'=>"L'intensité des ouragans",'correct'=>false],['text'=>"La température des volcans",'correct'=>false],['text'=>"La force des tsunamis",'correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // INFORMATIQUE
            // ──────────────────────────────────────────────────────────
            'informatique' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Que signifie l'acronyme HTML ?",
                 'explanation'=>"HTML signifie HyperText Markup Language, c'est le langage de balisage utilisé pour créer des pages web.",
                 'choices'=>[['text'=>'HyperText Markup Language','correct'=>true],['text'=>'High Tech Modern Language','correct'=>false],['text'=>'HyperText Modern Links','correct'=>false],['text'=>'Home Tool Markup Language','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Combien de bits composent un octet ?",
                 'explanation'=>"Un octet (byte) est composé de 8 bits, l'unité de base de l'information numérique.",
                 'choices'=>[['text'=>'8','correct'=>true],['text'=>'16','correct'=>false],['text'=>'4','correct'=>false],['text'=>'32','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel langage est principalement utilisé pour styliser les pages web ?",
                 'explanation'=>"CSS (Cascading Style Sheets) est le langage utilisé pour définir l'apparence et la mise en forme des pages web.",
                 'choices'=>[['text'=>'CSS','correct'=>true],['text'=>'JavaScript','correct'=>false],['text'=>'PHP','correct'=>false],['text'=>'SQL','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle structure de données fonctionne selon le principe LIFO ?",
                 'explanation'=>"La pile (stack) fonctionne selon le principe LIFO (Last In, First Out) : le dernier élément ajouté est le premier retiré.",
                 'choices'=>[['text'=>'La pile (stack)','correct'=>true],['text'=>'La file (queue)','correct'=>false],['text'=>"L'arbre binaire",'correct'=>false],['text'=>'La liste chaînée','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel protocole est utilisé pour sécuriser les communications web (HTTPS) ?",
                 'explanation'=>"TLS (Transport Layer Security), anciennement SSL, est le protocole cryptographique qui sécurise les communications HTTPS.",
                 'choices'=>[['text'=>'TLS/SSL','correct'=>true],['text'=>'HTTP','correct'=>false],['text'=>'FTP','correct'=>false],['text'=>'SSH','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quelle est la complexité temporelle d'un algorithme de tri rapide (quicksort) en moyenne ?",
                 'explanation'=>"Le tri rapide (quicksort) a une complexité temporelle moyenne de O(n log n), ce qui en fait l'un des algorithmes de tri les plus efficaces.",
                 'choices'=>[['text'=>'O(n log n)','correct'=>true],['text'=>'O(n²)','correct'=>false],['text'=>'O(n)','correct'=>false],['text'=>'O(log n)','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Que signifie SQL ?",
                 'explanation'=>"SQL signifie Structured Query Language, le langage standard pour interagir avec les bases de données relationnelles.",
                 'choices'=>[['text'=>'Structured Query Language','correct'=>true],['text'=>'Simple Question Language','correct'=>false],['text'=>'System Query Logic','correct'=>false],['text'=>'Standard Query List','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel théorème stipule qu'il est impossible d'avoir simultanément cohérence, disponibilité et tolérance aux partitions dans un système distribué ?",
                 'explanation'=>"Le théorème CAP (Brewer) démontre qu'un système distribué ne peut garantir que 2 des 3 propriétés : Cohérence, Disponibilité (Availability) et tolérance aux Partitions.",
                 'choices'=>[['text'=>'Le théorème CAP','correct'=>true],['text'=>'Le théorème ACID','correct'=>false],['text'=>'Le théorème BASE','correct'=>false],['text'=>'Le théorème SOLID','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel algorithme de chiffrement asymétrique est basé sur la factorisation de grands nombres premiers ?",
                 'explanation'=>"RSA (Rivest-Shamir-Adleman) est un algorithme de chiffrement asymétrique dont la sécurité repose sur la difficulté de factoriser de grands nombres en facteurs premiers.",
                 'choices'=>[['text'=>'RSA','correct'=>true],['text'=>'AES','correct'=>false],['text'=>'SHA-256','correct'=>false],['text'=>'DES','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qu'est-ce qu'une injection SQL ?",
                 'explanation'=>"Une injection SQL est une attaque qui consiste à insérer du code SQL malveillant dans une requête pour manipuler la base de données.",
                 'choices'=>[['text'=>"Une attaque qui insère du code SQL malveillant dans une requête",'correct'=>true],['text'=>"Un type de virus informatique",'correct'=>false],['text'=>"Une technique d'optimisation de base de données",'correct'=>false],['text'=>"Un protocole de sauvegarde",'correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // LITTÉRATURE
            // ──────────────────────────────────────────────────────────
            'litterature' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a écrit 'Les Misérables' ?",
                 'explanation'=>"Victor Hugo a écrit Les Misérables, publié en 1862, l'un des plus grands romans de la littérature française.",
                 'choices'=>[['text'=>'Victor Hugo','correct'=>true],['text'=>'Émile Zola','correct'=>false],['text'=>'Gustave Flaubert','correct'=>false],['text'=>'Honoré de Balzac','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel personnage est le héros du roman 'Don Quichotte' ?",
                 'explanation'=>"Don Quichotte est le personnage principal du roman éponyme de Miguel de Cervantes, publié au début du XVIIe siècle.",
                 'choices'=>[['text'=>'Don Quichotte de la Manche','correct'=>true],['text'=>'Sancho Pança','correct'=>false],['text'=>'Dulcinée','correct'=>false],['text'=>'Rocinante','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a écrit 'Romeo et Juliette' ?",
                 'explanation'=>"William Shakespeare a écrit Roméo et Juliette vers 1594-1596, l'une des plus célèbres tragédies de la littérature mondiale.",
                 'choices'=>[['text'=>'William Shakespeare','correct'=>true],['text'=>'Christopher Marlowe','correct'=>false],['text'=>'John Milton','correct'=>false],['text'=>'Geoffrey Chaucer','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Dans quel roman d'Albert Camus l'étranger Meursault tue-t-il un Arabe sur une plage ?",
                 'explanation'=>"Dans 'L'Étranger' (1942), Meursault tue un Arabe sur une plage en Algérie. Ce roman explore l'absurde et l'indifférence.",
                 'choices'=>[['text'=>"L'Étranger",'correct'=>true],['text'=>'La Peste','correct'=>false],['text'=>'La Chute','correct'=>false],['text'=>"L'Homme révolté",'correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui est l'auteur de 'À la recherche du temps perdu' ?",
                 'explanation'=>"Marcel Proust est l'auteur de cette œuvre monumentale en 7 volumes, considérée comme l'un des chefs-d'œuvre de la littérature du XXe siècle.",
                 'choices'=>[['text'=>'Marcel Proust','correct'=>true],['text'=>'André Gide','correct'=>false],['text'=>'Paul Valéry','correct'=>false],['text'=>'Louis-Ferdinand Céline','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel roman de George Orwell décrit une société totalitaire sous le contrôle de 'Big Brother' ?",
                 'explanation'=>"'1984' de George Orwell, publié en 1949, décrit une société dystopique sous la surveillance constante de Big Brother.",
                 'choices'=>[['text'=>'1984','correct'=>true],['text'=>'Le Meilleur des mondes','correct'=>false],['text'=>'Fahrenheit 451','correct'=>false],['text'=>'La Ferme des animaux','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Qui a écrit 'Madame Bovary' ?",
                 'explanation'=>"Gustave Flaubert a écrit Madame Bovary (1857), roman naturaliste qui relate les désillusions d'Emma Bovary.",
                 'choices'=>[['text'=>'Gustave Flaubert','correct'=>true],['text'=>'Stendhal','correct'=>false],['text'=>'Balzac','correct'=>false],['text'=>'Zola','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Dans quel siècle a vécu le poète François Villon ?",
                 'explanation'=>"François Villon (1431-v.1463) est un poète français du XVe siècle, auteur du Lais et du Testament.",
                 'choices'=>[['text'=>'XVe siècle','correct'=>true],['text'=>'XIVe siècle','correct'=>false],['text'=>'XVIe siècle','correct'=>false],['text'=>'XIIIe siècle','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel courant littéraire du XIXe siècle prône l'objectivité scientifique dans la représentation de la réalité sociale ?",
                 'explanation'=>"Le naturalisme, développé par Émile Zola, s'appuie sur des méthodes scientifiques pour décrire la réalité sociale et humaine.",
                 'choices'=>[['text'=>'Le naturalisme','correct'=>true],['text'=>'Le romantisme','correct'=>false],['text'=>'Le symbolisme','correct'=>false],['text'=>'Le parnasse','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Qui a écrit 'Le Petit Prince' et en quelle année ?",
                 'explanation'=>"Antoine de Saint-Exupéry a écrit Le Petit Prince, publié le 6 avril 1943 aux États-Unis, peu avant sa disparition en 1944.",
                 'choices'=>[['text'=>'Saint-Exupéry, 1943','correct'=>true],['text'=>'Jules Verne, 1872','correct'=>false],['text'=>'Albert Camus, 1942','correct'=>false],['text'=>'André Malraux, 1940','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // SPORT
            // ──────────────────────────────────────────────────────────
            'sport' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Combien de joueurs composent une équipe de football sur le terrain ?",
                 'explanation'=>"Une équipe de football est composée de 11 joueurs sur le terrain, dont un gardien de but.",
                 'choices'=>[['text'=>'11','correct'=>true],['text'=>'10','correct'=>false],['text'=>'12','correct'=>false],['text'=>'9','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans quel sport utilise-t-on un volant (shuttle) ?",
                 'explanation'=>"Le volant (shuttlecock) est utilisé dans le badminton, un sport de raquette.",
                 'choices'=>[['text'=>'Le badminton','correct'=>true],['text'=>'Le tennis','correct'=>false],['text'=>'Le squash','correct'=>false],['text'=>'Le ping-pong','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quelle est la longueur d'un marathon ?",
                 'explanation'=>"Un marathon mesure exactement 42,195 kilomètres, distance officiellement établie aux Jeux olympiques de 1908.",
                 'choices'=>[['text'=>'42,195 km','correct'=>true],['text'=>'40 km','correct'=>false],['text'=>'45 km','correct'=>false],['text'=>'38 km','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quelle nation a remporté le plus de Coupes du Monde de football ?",
                 'explanation'=>"Le Brésil a remporté 5 Coupes du Monde (1958, 1962, 1970, 1994, 2002), ce qui en fait la nation la plus titrée.",
                 'choices'=>[['text'=>'Le Brésil','correct'=>true],['text'=>"L'Allemagne",'correct'=>false],['text'=>"L'Italie",'correct'=>false],['text'=>"L'Argentine",'correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"En quelle année ont eu lieu les premiers Jeux olympiques modernes ?",
                 'explanation'=>"Les premiers Jeux olympiques modernes se sont tenus à Athènes, en Grèce, en 1896, à l'initiative de Pierre de Coubertin.",
                 'choices'=>[['text'=>'1896','correct'=>true],['text'=>'1900','correct'=>false],['text'=>'1888','correct'=>false],['text'=>'1904','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel sportif est surnommé 'The Greatest' ?",
                 'explanation'=>"Muhammad Ali, champion du monde de boxe poids lourds, est surnommé 'The Greatest' pour ses exploits sportifs et son charisme.",
                 'choices'=>[['text'=>'Muhammad Ali','correct'=>true],['text'=>'Mike Tyson','correct'=>false],['text'=>'Joe Frazier','correct'=>false],['text'=>'George Foreman','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Combien de sets faut-il gagner pour remporter un match de tennis en Grand Chelem (hommes) ?",
                 'explanation'=>"En Grand Chelem masculin, un joueur doit gagner 3 sets sur 5 pour remporter le match.",
                 'choices'=>[['text'=>'3 sets sur 5','correct'=>true],['text'=>'2 sets sur 3','correct'=>false],['text'=>'4 sets sur 7','correct'=>false],['text'=>'2 sets sur 5','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel est le record du monde du 100 mètres masculin et qui le détient ?",
                 'explanation'=>"Usain Bolt détient le record du monde du 100 mètres avec un temps de 9,58 secondes, établi à Berlin en 2009.",
                 'choices'=>[['text'=>"9,58 secondes (Usain Bolt)",'correct'=>true],['text'=>"9,69 secondes (Tyson Gay)",'correct'=>false],['text'=>"9,72 secondes (Asafa Powell)",'correct'=>false],['text'=>"9,81 secondes (Carl Lewis)",'correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Dans quel sport pratique-t-on le 'Fosbury Flop' ?",
                 'explanation'=>"Le Fosbury Flop est une technique de saut en hauteur consistant à passer la barre dos en premier, introduite par Dick Fosbury aux JO de 1968.",
                 'choices'=>[['text'=>'Le saut en hauteur','correct'=>true],['text'=>'Le saut en longueur','correct'=>false],['text'=>'Le saut à la perche','correct'=>false],['text'=>'Le triple saut','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Combien de fois le Tour de France a-t-il été remporté par Lance Armstrong avant sa disqualification ?",
                 'explanation'=>"Lance Armstrong a remporté 7 Tours de France consécutifs (1999-2005), mais a été disqualifié de tous en 2012 pour dopage.",
                 'choices'=>[['text'=>'7','correct'=>true],['text'=>'5','correct'=>false],['text'=>'6','correct'=>false],['text'=>'4','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // CINÉMA
            // ──────────────────────────────────────────────────────────
            'cinema' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Qui a réalisé 'Titanic' (1997) ?",
                 'explanation'=>"James Cameron a réalisé Titanic en 1997, film qui a remporté 11 Oscars dont Meilleur Film et Meilleur Réalisateur.",
                 'choices'=>[['text'=>'James Cameron','correct'=>true],['text'=>'Steven Spielberg','correct'=>false],['text'=>'Christopher Nolan','correct'=>false],['text'=>'Ridley Scott','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Quel film de Disney met en scène un poisson-clown nommé Némo ?",
                 'explanation'=>"'Le Monde de Némo' (2003) de Pixar/Disney raconte l'histoire d'un poisson-clown qui part à la recherche de son fils.",
                 'choices'=>[['text'=>'Le Monde de Némo','correct'=>true],['text'=>'Bambi','correct'=>false],['text'=>'Dumbo','correct'=>false],['text'=>'Pinocchio','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Dans quel film entend-on la célèbre réplique 'May the Force be with you' ?",
                 'explanation'=>"Cette réplique iconique vient de la saga Star Wars, créée par George Lucas en 1977.",
                 'choices'=>[['text'=>'Star Wars','correct'=>true],['text'=>'Star Trek','correct'=>false],['text'=>'Interstellar','correct'=>false],['text'=>'Alien','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel film de Stanley Kubrick est adapté d'un roman de Stephen King ?",
                 'explanation'=>"Shining (1980) de Stanley Kubrick est adapté du roman 'The Shining' de Stephen King, avec Jack Nicholson dans le rôle principal.",
                 'choices'=>[['text'=>'Shining','correct'=>true],['text'=>'2001: Odyssée de l\'espace','correct'=>false],['text'=>'Orange mécanique','correct'=>false],['text'=>'Full Metal Jacket','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui a incarné Iron Man dans les films Marvel ?",
                 'explanation'=>"Robert Downey Jr. a incarné Tony Stark / Iron Man dans les films du MCU de 2008 à 2019.",
                 'choices'=>[['text'=>'Robert Downey Jr.','correct'=>true],['text'=>'Chris Evans','correct'=>false],['text'=>'Chris Hemsworth','correct'=>false],['text'=>'Mark Ruffalo','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel film de Francis Ford Coppola suit la famille mafieuse Corleone ?",
                 'explanation'=>"Le Parrain (The Godfather, 1972) de Francis Ford Coppola est considéré comme l'un des plus grands films de l'histoire du cinéma.",
                 'choices'=>[['text'=>'Le Parrain','correct'=>true],['text'=>'Scarface','correct'=>false],['text'=>'Les Affranchis','correct'=>false],['text'=>'Casino','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quel est le film d'animation le plus rentable de tous les temps ?",
                 'explanation'=>"Le Roi Lion (2019, version live-action/animation) ou Le Lion (2019) a dépassé le milliard, mais Frozen II et certains Pixar dominent l'animation pure. La Reine des Neiges est la plus rentable en animation pure.",
                 'choices'=>[['text'=>'La Reine des Neiges (Frozen)','correct'=>true],['text'=>'Le Roi Lion','correct'=>false],['text'=>'Toy Story 4','correct'=>false],['text'=>'Incroyables 2','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Quel réalisateur français est considéré comme le père de la Nouvelle Vague ?",
                 'explanation'=>"Jean-Luc Godard est souvent considéré comme le père de la Nouvelle Vague française avec 'À bout de souffle' (1960).",
                 'choices'=>[['text'=>'Jean-Luc Godard','correct'=>true],['text'=>'François Truffaut','correct'=>false],['text'=>'Claude Chabrol','correct'=>false],['text'=>'Jacques Rivette','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Dans 'Inception' (2010), combien de niveaux de rêves les personnages explorent-ils ?",
                 'explanation'=>"Dans Inception de Christopher Nolan, les personnages explorent 4 niveaux de rêves imbriqués, plus le 'limbe'.",
                 'choices'=>[['text'=>'4 niveaux (+ le limbe)','correct'=>true],['text'=>'3 niveaux','correct'=>false],['text'=>'5 niveaux','correct'=>false],['text'=>'2 niveaux','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel film a remporté la Palme d'Or à Cannes en 2019 ?",
                 'explanation'=>"Parasite (기생충) de Bong Joon-ho a remporté la Palme d'Or à Cannes en 2019, puis l'Oscar du Meilleur Film en 2020.",
                 'choices'=>[['text'=>'Parasite (Bong Joon-ho)','correct'=>true],['text'=>'Portrait de la jeune fille en feu','correct'=>false],['text'=>'Atlantique','correct'=>false],['text'=>'It Must Be Heaven','correct'=>false]]],
            ],

            // ──────────────────────────────────────────────────────────
            // MUSIQUE
            // ──────────────────────────────────────────────────────────
            'musique' => [
                ['difficulty'=>'easy','time'=>15,'text'=>"Quel groupe britannique est surnommé 'The Fab Four' ?",
                 'explanation'=>"Les Beatles sont surnommés 'The Fab Four' en référence à leurs quatre membres : John Lennon, Paul McCartney, George Harrison et Ringo Starr.",
                 'choices'=>[['text'=>'The Beatles','correct'=>true],['text'=>'The Rolling Stones','correct'=>false],['text'=>'The Who','correct'=>false],['text'=>'Led Zeppelin','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"Qui est surnommé 'The King' dans le monde du rock ?",
                 'explanation'=>"Elvis Presley est surnommé 'The King' (du rock and roll) pour son influence majeure sur la musique populaire dans les années 1950-1970.",
                 'choices'=>[['text'=>'Elvis Presley','correct'=>true],['text'=>'Chuck Berry','correct'=>false],['text'=>'Little Richard','correct'=>false],['text'=>'Jerry Lee Lewis','correct'=>false]]],

                ['difficulty'=>'easy','time'=>15,'text'=>"De combien de cordes est composée une guitare classique ?",
                 'explanation'=>"Une guitare classique (acoustique) est composée de 6 cordes, accordées de la plus grave à la plus aiguë : Mi La Ré Sol Si Mi.",
                 'choices'=>[['text'=>'6','correct'=>true],['text'=>'4','correct'=>false],['text'=>'8','correct'=>false],['text'=>'12','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Qui a composé la célèbre 'Lettre à Élise' ?",
                 'explanation'=>"La Lettre à Élise (Bagatelle en la mineur) est une composition de Ludwig van Beethoven, écrite vers 1810.",
                 'choices'=>[['text'=>'Ludwig van Beethoven','correct'=>true],['text'=>'Wolfgang Amadeus Mozart','correct'=>false],['text'=>'Frédéric Chopin','correct'=>false],['text'=>'Franz Schubert','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel genre musical est originaire de La Nouvelle-Orléans ?",
                 'explanation'=>"Le jazz est né à La Nouvelle-Orléans à la fin du XIXe siècle, fusionnant blues, ragtime et musique africaine.",
                 'choices'=>[['text'=>'Le jazz','correct'=>true],['text'=>'Le blues','correct'=>false],['text'=>'Le gospel','correct'=>false],['text'=>'Le country','correct'=>false]]],

                ['difficulty'=>'medium','time'=>30,'text'=>"Quel chanteur français est l'auteur de 'La Vie en Rose' ?",
                 'explanation'=>"Édith Piaf a écrit et interprété 'La Vie en Rose' en 1946, chanson devenue un symbole de la chanson française.",
                 'choices'=>[['text'=>'Édith Piaf','correct'=>true],['text'=>'Charles Aznavour','correct'=>false],['text'=>'Juliette Gréco','correct'=>false],['text'=>'Maurice Chevalier','correct'=>false]]],

                ['difficulty'=>'medium','time'=>35,'text'=>"Quel instrument de musique est associé au musicien de jazz Miles Davis ?",
                 'explanation'=>"Miles Davis était un trompettiste de jazz américain, l'une des figures les plus influentes du jazz du XXe siècle.",
                 'choices'=>[['text'=>'La trompette','correct'=>true],['text'=>'Le saxophone','correct'=>false],['text'=>'La contrebasse','correct'=>false],['text'=>'La batterie','correct'=>false]]],

                ['difficulty'=>'hard','time'=>50,'text'=>"Combien de symphonies Beethoven a-t-il composé ?",
                 'explanation'=>"Ludwig van Beethoven a composé 9 symphonies, dont la célèbre 9e symphonie (Hymne à la joie) composée alors qu'il était sourd.",
                 'choices'=>[['text'=>'9','correct'=>true],['text'=>'6','correct'=>false],['text'=>'12','correct'=>false],['text'=>'41','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel compositeur baroque est l'auteur des 'Quatre Saisons' ?",
                 'explanation'=>"Les Quatre Saisons sont un ensemble de quatre concertos pour violon composés par Antonio Vivaldi, publié en 1725.",
                 'choices'=>[['text'=>'Antonio Vivaldi','correct'=>true],['text'=>'Johann Sebastian Bach','correct'=>false],['text'=>'Georg Friedrich Händel','correct'=>false],['text'=>'Claudio Monteverdi','correct'=>false]]],

                ['difficulty'=>'hard','time'=>55,'text'=>"Quel mouvement musical des années 1970 est caractérisé par des chansons courtes, rapides et une attitude rebelle ?",
                 'explanation'=>"Le punk rock, apparu à New York et Londres à la fin des années 1970, est caractérisé par des chansons courtes, rapides et une attitude anti-establishment.",
                 'choices'=>[['text'=>'Le punk rock','correct'=>true],['text'=>'Le heavy metal','correct'=>false],['text'=>'La new wave','correct'=>false],['text'=>'Le grunge','correct'=>false]]],
            ],
        ];
    }
}
