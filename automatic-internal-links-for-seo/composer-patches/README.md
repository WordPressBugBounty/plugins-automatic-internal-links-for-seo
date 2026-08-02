# Correctif Pagup pour `friedolinfoerder/html-changer` 0.1.6

Le paquet amont `friedolinfoerder/html-changer` 0.1.6 ne déclare pas la
propriété `EndingTag::$attributes` qu’il utilise et transmet parfois `null` à
`mb_strtolower()`. PHP 8.4 exige aussi que le paramètre nullable de `parts()`
soit déclaré explicitement. Les versions PHP actuelles signalent ces contrats
implicites comme dépréciés.

`apply-html-changer-php83.php` applique trois remplacements exacts après chaque
`composer install` ou `composer update` :

1. déclaration de `EndingTag::$attributes` avec la valeur historique vide;
2. conversion explicite du caractère suivant en chaîne avant
   `mb_strtolower()`;
3. déclaration nullable explicite du tableau d’exclusions de `parts()`.

Le script n’accepte que les empreintes SHA-256 amont connues, vérifie
l’empreinte de sortie et est idempotent. Un changement de la dépendance amont
fait donc échouer Composer au lieu d’appliquer silencieusement un correctif à
des octets inconnus. Toute exécution hors de l’interface en ligne de commande
est refusée avant la lecture ou l’écriture d’un fichier.

Ce répertoire est distribué avec les paquets gratuit et premium afin que leur
`composer.json` reste installable de manière autonome. La copie à la racine du
dépôt est canonique; la CI exige une copie premium identique octet pour octet.
