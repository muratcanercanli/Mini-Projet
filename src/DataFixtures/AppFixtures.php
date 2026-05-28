<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // ── Catégories ──────────────────────────────────────────────────────
        $categoryData = [
            ['Voiture',     'Véhicules particuliers neufs et d\'occasion toutes marques'],
            ['Moto',        'Motos, scooters et deux-roues motorisés'],
            ['Utilitaire',  'Fourgonnettes, vans et véhicules utilitaires légers'],
        ];

        $categories = [];
        foreach ($categoryData as [$name, $desc]) {
            $cat = (new Category())->setName($name)->setDescription($desc);
            $manager->persist($cat);
            $categories[] = $cat;
        }

        // ── Utilisateurs ─────────────────────────────────────────────────────
        $admin = new User();
        $admin->setName('Admin')
              ->setSurname('Boutique')
              ->setEmail('admin@motostore.fr')
              ->setPassword($this->hasher->hashPassword($admin, 'admin1234'))
              ->setAddress('1 Rue de l\'Administration, 44000 Nantes')
              ->setPhoneNumber('0600000001')
              ->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $sellerData = [
            ['Dupont',    'Pierre',   'pierre.dupont@mail.fr'],
            ['Martin',    'Sophie',   'sophie.martin@mail.fr'],
            ['Leroy',     'Thomas',   'thomas.leroy@mail.fr'],
            ['Bernard',   'Camille',  'camille.bernard@mail.fr'],
            ['Petit',     'Lucas',    'lucas.petit@mail.fr'],
            ['Moreau',    'Inès',     'ines.moreau@mail.fr'],
        ];

        $sellers = [$admin];
        foreach ($sellerData as [$name, $surname, $email]) {
            $user = new User();
            $user->setName($name)
                 ->setSurname($surname)
                 ->setEmail($email)
                 ->setPassword($this->hasher->hashPassword($user, 'password123'))
                 ->setAddress($faker->address())
                 ->setPhoneNumber($faker->phoneNumber())
                 ->setRoles([]);
            $manager->persist($user);
            $sellers[] = $user;
        }

        // ── Produits ──────────────────────────────────────────────────────────
        // [catIndex, nom, description, prix (€), stock, stockMin]
        $vehicleData = [

            // ── Voitures ──
            [0, 'Renault Clio V 1.0 TCe 90',
                'Citadine polyvalente en excellent état, première main. Équipée de la climatisation automatique, du GPS Tomtom et du régulateur de vitesse adaptatif. Révision récente, carnet complet.',
                14500, 5, 2],

            [0, 'Peugeot 208 1.2 PureTech 75',
                'Compacte moderne avec faible kilométrage (28 000 km). Intérieur semi-cuir, toit panoramique ouvrant, caméra de recul, aide au stationnement avant/arrière.',
                12900, 3, 2],

            [0, 'Volkswagen Golf VIII 1.5 TSI 130',
                'Berline compacte premium avec boîte DSG7. LED matrix, Digital Cockpit 10,25 pouces, App-Connect sans fil. Garantie constructeur jusqu\'en 2026.',
                22500, 2, 2],

            [0, 'Toyota Yaris 1.5 Hybrid 116',
                'Hybride économique et ultra-fiable : 3,8 L/100 km en usage mixte. Finition Design avec sièges chauffants, affichage tête haute et Toyota Safety Sense.',
                19800, 1, 2],

            [0, 'Ford Focus Break 1.0 EcoBoost 125',
                'Break familial spacieux (coffre 608 L). Sièges avant chauffants, Apple CarPlay / Android Auto sans fil, régulateur de vitesse adaptatif, détection de fatigue.',
                16200, 4, 2],

            [0, 'Citroën C3 1.2 PureTech 83',
                'Citadine créative avec Airbump® latéraux. Finition Shine, Connect Nav 7 pouces, rétroviseurs rabattables électriquement. Parfaite en ville.',
                11500, 0, 2],

            [0, 'Dacia Sandero Stepway TCe 90',
                'SUV urbain abordable et robuste, rapport qualité-prix imbattable. Finition Confort avec climatisation, GPS MediaNav et capteurs de stationnement arrière.',
                13990, 8, 3],

            [0, 'BMW Série 1 118i 136 DKG',
                'Premium compacte traction avant. Cockpit numérique Live Cockpit Professional, système de navigation iDrive 7, éclairage intérieur ambiance 16 couleurs.',
                28900, 1, 2],

            [0, 'Mercedes Classe A 180 d 116',
                'Berline premium élégante boîte 7G-DCT. Système MBUX avec commande vocale Hey Mercedes, sièges cuir, aide au stationnement actif, ambiance lumineuse.',
                31500, 2, 2],

            [0, 'Kia Niro EV 204 ch',
                'SUV 100 % électrique, 460 km d\'autonomie WLTP. Charge rapide DC 100 kW (30 à 80 % en 43 min), garantie batterie 10 ans, Head-up display, toit ouvrant.',
                37900, 3, 2],

            [0, 'Opel Corsa-e 136 ch',
                'Citadine 100 % électrique, 338 km d\'autonomie (WLTP). Charge rapide 100 kW, instrumentation Pure Panel numérique, caméra de recul, régulateur adaptatif.',
                27500, 0, 2],

            [0, 'Skoda Octavia Combi 2.0 TDI 150',
                'Break familial tchèque XXL (coffre 640 L). Full LED matrix, régulateur adaptatif ACC, assistant de maintien de voie, affichage tête haute. Excellent rapport qualité-prix.',
                24700, 6, 3],

            // ── Motos ──
            [1, 'Honda CB500F 2023',
                'Roadster accessible et polyvalent, idéal permis A2 (47 ch bridés). Moteur bicylindre parallèle 500 cm³, consommation moyenne 5 L/100 km, ABS de série. Révision faite.',
                7200, 4, 2],

            [1, 'Kawasaki Z400 2023',
                'Petit roadster sportif et maniable, homologable A2 (45 ch). Look agressif inspiré de la Z900, châssis périmétrique acier, suspensions réglables, ABS Bosch.',
                6500, 2, 2],

            [1, 'Yamaha MT-07 2022',
                'Roadster mid-size au caractère affirmé. Moteur bicylindre CP2 700 cm³ (73 ch), tableau de bord TFT couleur 5 pouces, Quick Shift, contrôle de traction réglable.',
                8400, 3, 2],

            [1, 'Triumph Street Triple 765 RS',
                'Roadster sportif de référence. Moteur trois cylindres 123 ch, fourche Öhlins NIX30, prise USB-C, Cornering ABS, modes de conduite Road / Rain / Sport / Track.',
                13200, 1, 2],

            [1, 'Ducati Monster 937',
                'Icône bolognaise repensée. Moteur Testastretta 11° 111 ch, cadre monocoque aluminium, aides électroniques Bosch, écran TFT 4,3 pouces, look indémodable.',
                14800, 2, 2],

            [1, 'Honda Forza 750 2023',
                'Grand scooter GT puissant et confortable. Moteur bicylindre 58 ch, double embrayage DCT, coffre sous la selle 22 L, pare-brise réglable électriquement, chauffage poignées.',
                10500, 0, 2],

            [1, 'Suzuki GSX-8S 2023',
                'Roadster polyvalent nouvelle génération (83 ch). Disponible bridé A2. Équipement complet série : TFT couleur, IMU, contrôle de traction, feux full LED.',
                8900, 5, 3],

            [1, 'KTM 390 Duke 2024',
                'Roadster léger et agile pour la ville. Monocylindre 44 ch, châssis tubulaire acier, écran TFT Bluetooth, WP Apex réglables, ABS désactivable et supermoto.',
                5800, 1, 2],

            [1, 'BMW R 1250 GS Adventure',
                'Trail grand voyageur de référence mondiale. Boxer 136 ch, Dynamic ESA Telelever/EVO Paralever, GPS Connectivity intégré, autonomie 500 km, réservoirs 30 L.',
                22900, 0, 2],

            [1, 'Harley-Davidson Sportster S 1250',
                'Custom américain au moteur Revolution Max 121 ch. Cadre aluminium coulé, Cornering ABS, contrôle de traction, 3 modes de conduite, écran TFT 4 pouces.',
                17500, 3, 2],

            // ── Utilitaires ──
            [2, 'Renault Kangoo Express L1 1.5 dCi 95',
                'Fourgonnette compacte urbaine incontournable. Volume utile 3,3 m³, charge utile 650 kg, cloison de séparation bois, éco-mode, connectivité Easy Link.',
                18500, 4, 2],

            [2, 'Citroën Berlingo Van M 1.5 BlueHDi 100',
                'Utilitaire pratique et économique. Hayon battant ou coulissant latéral, volume 3,3 m³, plancher plat, boîte automatique EAT8 disponible, Mirror Screen.',
                19200, 3, 2],

            [2, 'Volkswagen Caddy Cargo 2.0 TDI 102',
                'Compact premium robuste et fiable. Volume 3,1 m³, nombreux rangements latéraux, App-Connect sans fil, freinage d\'urgence Front Assist de série.',
                23900, 2, 2],

            [2, 'Peugeot Partner Long 1.5 BlueHDi 130',
                'Version allongée pour plus de capacité (3,8 m³). Charge utile jusqu\'à 800 kg, GPS intégré 3D Connected, caméra de recul, régulateur de vitesse adaptatif.',
                21500, 5, 2],

            [2, 'Mercedes Citan Fourgon 110 CDI',
                'Utilitaire compact premium à 3 sièges. Construction robuste inspirée du Kangoo, assistance au freinage d\'urgence ATTENTION ASSIST, connectivité avancée.',
                24800, 1, 2],

            [2, 'Ford Transit Connect L2 2.0 TDCi 120',
                'Fourgonnette allongée polyvalente. Volume 3,6 m³, charge utile 820 kg, plancher en bois, Ford SYNC 3 avec navigation, 8 prises USB et 12V.',
                22300, 0, 2],

            [2, 'Renault Trafic Fourgon L1H1 2.0 dCi 130',
                'Fourgon moyen volume incontournable. Charge utile 1 200 kg, volume 5,2 m³, Easy Link 8 pouces avec Android Auto, caméra 360°, hayon électrique.',
                31500, 3, 3],

            [2, 'Volkswagen Transporter T6.1 2.0 TDI 150',
                'Référence des fourgons moyens toutes générations. Charge utile 1 100 kg, boîte DSG disponible, Digital Cockpit Pro, ambiance intérieure premium.',
                38900, 2, 2],

            [2, 'Citroën Jumpy M 2.0 BlueHDi 145',
                'Fourgon medium polyvalent. Volume 5,1 m³, charge utile 1 400 kg, sièges confort suspension active, Wifi embarqué, régulateur de vitesse adaptatif radar.',
                34200, 0, 2],

            [2, 'Nissan Townstar Electrique Van L1',
                'Fourgonnette 100 % électrique zéro émission. Autonomie 285 km WLTP, charge rapide 75 kW (0 à 80 % en 40 min), e-Pedal, Pro Pilot avec centrage de voie.',
                32900, 1, 2],
        ];

        foreach ($vehicleData as [$catIdx, $name, $desc, $price, $stock, $stockMin]) {
            $createdAt = $faker->dateTimeBetween('-18 months', '-2 months');
            $updatedAt = $faker->dateTimeBetween($createdAt, 'now');

            $product = new Product();
            $product->setName($name)
                    ->setDescription($desc)
                    ->setPrice($price)
                    ->setStock($stock)
                    ->setStockMin($stockMin)
                    ->setCreationDate($createdAt)
                    ->setModificationDate($updatedAt)
                    ->setCategory($categories[$catIdx])
                    ->setSeller($faker->randomElement($sellers));
            $manager->persist($product);
        }

        $manager->flush();
    }
}
