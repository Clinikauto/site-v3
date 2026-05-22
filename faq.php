<?php
require_once __DIR__ . '/includes/catalog_store.php';
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$showAdminReturn = catalog_is_admin_session_active();
catalog_track_visit('faq_page');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ Garage Scionzier (74950) | Questions Frequentes | Clinik Auto</title>
    <meta name="description" content="FAQ Clinik Auto a Scionzier (74950) : prestations, qualite du travail, rendez-vous, garantie constructeur, devis, pieces et vehicules d'occasion.">
    <meta name="keywords" content="FAQ garage Scionzier, questions fréquentes garage auto, rendez-vous révision, garantie constructeur garage indépendant, durée révision auto, devis garage Scionzier">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Clinik Auto">
    <meta name="geo.region" content="FR-74">
    <meta name="geo.placename" content="Scionzier">
    <meta name="geo.position" content="46.0608;6.5394">
    <link rel="canonical" href="https://www.clinikauto.fr/faq.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="FAQ Clinik Auto | Garage automobile a Scionzier (74)">
    <meta property="og:description" content="Toutes les reponses aux questions clients sur nos prestations, la qualite de notre travail, les rendez-vous, la revision, les pieces et les VO.">
    <meta property="og:url" content="https://www.clinikauto.fr/faq.php">
    <meta property="og:image" content="https://www.clinikauto.fr/assets/logo.png">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="Clinik Auto">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="FAQ Clinik Auto | Scionzier">
    <meta name="twitter:description" content="FAQ clients Clinik Auto : prestations, qualite du travail, revision, garantie, pieces et rendez-vous.">
    <meta name="twitter:image" content="https://www.clinikauto.fr/assets/logo.png">
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="stylesheet" href="assets/style.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "BreadcrumbList",
                "itemListElement": [
                    { "@type": "ListItem", "position": 1, "name": "Accueil", "item": "https://www.clinikauto.fr/" },
                    { "@type": "ListItem", "position": 2, "name": "F.A.Q", "item": "https://www.clinikauto.fr/faq.php" }
                ]
            },
            {
                "@type": "AutoRepair",
                "@id": "https://www.clinikauto.fr/#garage",
                "name": "Clinik Auto",
                "url": "https://www.clinikauto.fr/",
                "logo": "https://www.clinikauto.fr/assets/logo.png",
                "telephone": "+33620185627",
                "email": "clinikauto74@gmail.com",
                "address": {
                    "@type": "PostalAddress",
                    "streetAddress": "118 Clos des Teppes",
                    "addressLocality": "Scionzier",
                    "postalCode": "74950",
                    "addressRegion": "Haute-Savoie",
                    "addressCountry": "FR"
                }
            },
            {
                "@type": "WebPage",
                "url": "https://www.clinikauto.fr/faq.php",
                "name": "F.A.Q Clinik Auto - Questions fréquentes",
                "description": "Reponses aux questions frequentes sur les prestations, la qualite des interventions et les services de Clinik Auto a Scionzier.",
                "isPartOf": { "@id": "https://www.clinikauto.fr/#website" },
                "about": { "@id": "https://www.clinikauto.fr/#garage" }
            },
            {
                "@type": "FAQPage",
                "@id": "https://www.clinikauto.fr/faq.php#faq",
                "mainEntity": [
                    {
                        "@type": "Question",
                        "name": "Faut-il prendre rendez-vous pour venir ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Oui, et nous vous le recommandons vivement ! Un rendez-vous chez Clinik Auto, c'est la garantie d'un accueil optimal et d'un délai d'attente minimal. Appelez-nous ou contactez-nous pour trouver le créneau qui vous convient le mieux."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "La révision chez vous annule-t-elle la garantie constructeur ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Non. Depuis le règlement européen d'exemption par catégorie, vous pouvez faire entretenir votre véhicule sous garantie dans n'importe quel garage indépendant agréé, à condition d'utiliser des pièces de qualité équivalente. Clinik Auto respecte ces exigences."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Peut-on attendre sur place ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Malheureusement, nous ne disposons pas d'espace d'attente chez Clinik Auto. L'atelier présente des risques liés à l'activité mécanique et reste réservé à notre équipe pour votre sécurité. Nous vous tenons informé tout au long de l'intervention et vous appelons dès que votre véhicule est prêt."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "J'ai déjà mes pièces, puis-je venir avec ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Non. Chez Clinik Auto, travailler avec mes propres pièces me permet de garantir leur origine, leur qualité, et de couvrir chaque intervention par une garantie. Je ne peux donc pas monter des pièces apportées par le client."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Utilisez-vous des pièces d'origine ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Oui. Clinik Auto utilise des pièces d'origine constructeur ou des pièces homologuées de qualité équivalente, sélectionnées pour leur fiabilité, leur traçabilité et leur garantie."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Combien de temps dure une révision ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "La durée d'une révision dépend des opérations à réaliser. Pour une révision simple - vidange, filtres et contrôle général - comptez en moyenne entre 1h et 2h. Lors de votre demande de devis, nous vous détaillons les interventions prévues et leur durée estimée. Le délai définitif vous est ensuite confirmé à la prise de rendez-vous, selon le planning de Clinik Auto."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Peut-on déposer son véhicule le matin avant l'ouverture ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Contactez-nous pour convenir d'un dépôt anticipé. Nous essayons de nous adapter aux contraintes horaires de nos clients."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Proposez-vous un véhicule de remplacement pendant les réparations ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Nous ne disposons pas de flotte de véhicules de prêt permanente. Nous vous conseillons d'en faire la demande lors de votre prise de rendez-vous afin d'explorer ensemble les solutions disponibles et d'anticiper votre mobilité pendant la durée de l'intervention."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Intervenez-vous sur les véhicules récents ou seulement les anciens modèles ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Clinik Auto intervient sur les véhicules de toutes générations : récents, anciens, thermiques. Nos outillages et notre matériel de diagnostic multimarque sont adaptés aux modèles actuels. Les véhicules électriques ne sont pas pris en charge."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Faites-vous les contrôles techniques ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Oui, Clinik Auto ne réalise pas les contrôles techniques mais s'occupe de toute la préparation : entretien, réparation et mise en conformité de votre véhicule. Nous collaborons avec un centre de contrôle technique partenaire pour le passage au contrôle ou en contre-visite, quel que soit le centre ayant effectué le contrôle initial."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Faut-il être client régulier pour avoir un bon suivi ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Tous nos clients bénéficient du même niveau d'attention. Nous conservons un historique de vos interventions pour un meilleur suivi sur la durée."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Pouvez-vous m'aider à trouver un véhicule ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Absolument. Lors de votre demande de VO, précisez vos besoins (type, marque, budget, etc.) et nous orienterons notre sélection en conséquence."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Comment obtenir un devis ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Utilisez le formulaire de devis en ligne. Nous répondons dans les meilleurs délais."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Peut-on réserver des pièces auto d'occasion ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Oui, et la démarche est simple. Sélectionnez les pièces qui vous intéressent directement sur notre site et regroupez-les en une seule demande de réservation. Un acompte de 30 % par virement instantané est requis pour valider votre réservation. L'annonce reste visible en ligne jusqu'à ce que la transaction soit finalisée physiquement, garantissant ainsi la disponibilité réelle de la pièce."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Peut-on acheter un véhicule d'occasion directement depuis le site ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Non, et c'est voulu ! Chez Clinik Auto, nous pensons qu'un véhicule s'achète après l'avoir vu et essayé. Notre site vous permet de consulter nos annonces et de repérer le véhicule qui vous correspond. Il ne vous reste plus qu'à nous contacter pour organiser un essai ou un rendez-vous, et conclure la vente en toute confiance, sur place."
                        }
                    },
                    {
                        "@type": "Question",
                        "name": "Pourquoi avoir créé le site web Clinik Auto ?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": "Le site web a ete cree pour vous faire gagner du temps et mieux vous informer : comprendre nos prestations, verifier la disponibilite de nos annonces, demander un devis et prendre rendez-vous plus facilement, avec la meme exigence de clarte et de qualite que dans notre atelier."
                        }
                    }
                ]
            }
        ]
    }
    </script>
    <?php echo catalog_get_google_analytics_script(); ?>
</head>
<body class="public-page">
<header>
    <div class="site-brand">
        <a class="site-brand-link" href="index.html" aria-label="Clinik Auto accueil">
            <img class="site-logo" src="assets/logo.png" alt="Logo Clinik Auto">
        </a>
    </div>
    <nav>
        <ul>
            <li><a href="index.html">Accueil</a></li>
            <li><a href="catalogue/catalogue.php">Catalogue</a></li>
            <li><a href="devis/devis.php">Devis gratuit</a></li>
            <li><a href="rdv/rdv.php">Rendez-vous</a></li>
            <li><a href="contact/contact.php">Contact</a></li>
            <?php if ($showAdminReturn): ?><li><a href="admin.php">Retour admin</a></li><?php endif; ?>
        </ul>
    </nav>
</header>

<main class="page-main-hero">
    <section>
            <span class="hero-badge">⭐ Votre garage de confiance</span>
            <h1>Garage automobile à Scionzier : révision, réparation et vente d'occasion</h1>
            <p>Révision, entretien, réparation multimarque et vente de véhicules d'occasion à Scionzier (74950) — devis gratuit, réponse rapide.</p>
        </section>

    <section class="spaced-section" id="faq">
        <h2>Questions fréquentes</h2>
        <div class="review-card">
            <p><strong>Faut-il prendre rendez-vous pour venir ?</strong></p>
            <p>Oui, et nous vous le recommandons vivement ! Un rendez-vous chez Clinik Auto, c'est la garantie d'un accueil optimal et d'un délai d'attente minimal. Appelez-nous ou contactez-nous pour trouver le créneau qui vous convient le mieux.</p>

            <p><strong>La révision chez vous annule-t-elle la garantie constructeur ?</strong></p>
            <p>Non. Depuis le règlement européen d'exemption par catégorie, vous pouvez faire entretenir votre véhicule sous garantie dans n'importe quel garage indépendant agréé, à condition d'utiliser des pièces de qualité équivalente. Clinik Auto respecte ces exigences.</p>

            <p><strong>Peut-on attendre sur place ?</strong></p>
            <p>Malheureusement, nous ne disposons pas d'espace d'attente chez Clinik Auto. L'atelier présente des risques liés à l'activité mécanique et reste réservé à notre équipe pour votre sécurité. Pas d'inquiétude, nous vous tenons informé tout au long de l'intervention : si un problème supplémentaire est détecté au démontage, nous vous contactons immédiatement pour vous en informer et convenir ensemble de la marche à suivre. Nous vous appelons également dès que votre véhicule est prêt !</p>

            <p><strong>J'ai déjà mes pièces, puis-je venir avec ?</strong></p>
            <p>Non. Chez Clinik Auto, travailler avec mes propres pièces me permet de garantir leur origine, leur qualité, et de couvrir chaque intervention par une garantie. Je ne peux donc pas monter des pièces apportées par le client.</p>

            <p><strong>Utilisez-vous des pièces d'origine ?</strong></p>
            <p>Oui. Clinik Auto utilise des pièces d'origine constructeur ou des pièces homologuées de qualité équivalente, sélectionnées pour leur fiabilité, leur traçabilité et leur garantie.</p>

            <p><strong>Combien de temps dure une révision ?</strong></p>
            <p>La durée d'une révision dépend des opérations à réaliser. Pour une révision simple — vidange, filtres et contrôle général — comptez en moyenne entre 1h et 2h. Lors de votre demande de devis, nous vous détaillons les interventions prévues et leur durée estimée. Le délai définitif vous est ensuite confirmé à la prise de rendez-vous, selon le planning de Clinik Auto.</p>

            <p><strong>Peut-on déposer son véhicule le matin avant l'ouverture ?</strong></p>
            <p>Contactez-nous pour convenir d'un dépôt anticipé. Nous essayons de nous adapter aux contraintes horaires de nos clients.</p>

            <p><strong>Proposez-vous un véhicule de remplacement pendant les réparations ?</strong></p>
            <p>Nous ne disposons pas de flotte de véhicules de prêt permanente. Nous vous conseillons d'en faire la demande lors de votre prise de rendez-vous afin d'explorer ensemble les solutions disponibles et d'anticiper votre mobilité pendant la durée de l'intervention.</p>

            <p><strong>Intervenez-vous sur les véhicules récents ou seulement les anciens modèles ?</strong></p>
            <p>Clinik Auto intervient sur les véhicules de toutes générations : récents, anciens, thermiques. Nos outillages et notre matériel de diagnostic multimarque sont adaptés aux modèles actuels. Les véhicules électriques ne sont pas pris en charge.</p>

            <p><strong>Faites-vous les contrôles techniques ?</strong></p>
            <p>Oui, Clinik Auto ne réalise pas les contrôles techniques mais s'occupe de toute la préparation : entretien, réparation et mise en conformité de votre véhicule. Nous collaborons avec un centre de contrôle technique partenaire pour le passage au contrôle ou en contre-visite, quel que soit le centre ayant effectué le contrôle initial.</p>

            <p><strong>Faut-il être client régulier pour avoir un bon suivi ?</strong></p>
            <p>Tous nos clients bénéficient du même niveau d'attention. Nous conservons un historique de vos interventions pour un meilleur suivi sur la durée.</p>

            <p><strong>Pouvez-vous m'aider à trouver un véhicule ?</strong></p>
            <p>Absolument. Lors de votre demande de VO, précisez vos besoins (type, marque, budget, etc.) et nous orienterons notre sélection en conséquence.</p>

            <p><strong>Comment obtenir un devis ?</strong></p>
            <p>Utilisez le formulaire de devis en ligne. Nous répondons dans les meilleurs délais.</p>

            <p><strong>Peut-on réserver des pièces auto d'occasion ?</strong></p>
            <p>Oui, et la démarche est simple. Sélectionnez les pièces qui vous intéressent directement sur notre site et regroupez-les en une seule demande de réservation. Un acompte de 30 % par virement instantané est requis pour valider votre réservation. L'annonce reste visible en ligne jusqu'à ce que la transaction soit finalisée physiquement, garantissant ainsi la disponibilité réelle de la pièce.</p>

            <p><strong>Peut-on acheter un véhicule d'occasion directement depuis le site ?</strong></p>
            <p>Non, et c'est voulu ! Chez Clinik Auto, nous pensons qu'un véhicule s'achète après l'avoir vu et essayé. Notre site vous permet de consulter nos annonces et de repérer le véhicule qui vous correspond. Il ne vous reste plus qu'à nous contacter pour organiser un essai ou un rendez-vous - et conclure la vente en toute confiance, sur place.</p>

            <p><strong>Pourquoi avoir créé le site web Clinik Auto ?</strong></p>
            <p>Le site web a été créé pour vous faire gagner du temps et mieux vous informer : comprendre nos prestations, vérifier la disponibilité de nos annonces, demander un devis et prendre rendez-vous plus facilement, avec la même exigence de clarté et de qualité que dans notre atelier.</p>
        </div>
    </section>

    <section class="cta-section">
        <h2>Une question complémentaire ?</h2>
        <p>Contactez-nous et nous vous répondons rapidement selon votre besoin.</p>
        <div class="cta-inline">
            <a href="contact/contact.php" class="cta-link">Nous contacter</a>
            <a href="devis/devis.php" class="cta-link-secondary">Faire un devis en ligne</a>
        </div>
    </section>
</main>

<footer>
    <address>
        <strong>Clinik Auto</strong> &mdash; Garage Automobile<br>
        118 Clos des Teppes, 74950 Scionzier<br>
        <a href="tel:+33620185627">06 20 18 56 27</a> &bull;
        <a href="mailto:clinikauto74@gmail.com">clinikauto74@gmail.com</a>
    </address>
    <p class="footer-hours">Lun&ndash;Ven : 9h&ndash;12h / 14h&ndash;18h &bull; Sam : 9h&ndash;12h &bull; Dim : Ferm&eacute;</p>
    <p class="footer-social">
        <a href="https://www.facebook.com/Clinik.Auto" target="_blank" rel="noopener noreferrer" aria-label="Page Facebook Clinik Auto">&#x1F4D8; Suivez-nous sur Facebook</a>
    </p>
    <p class="footer-copy">&copy; 2026 Clinik Auto. Tous droits r&eacute;serv&eacute;s.</p>
</footer>
<button class="site-back-top-floating" id="site-back-top" aria-label="Retour en haut de la page">↑</button>
<script>!function(){var b=document.getElementById('site-back-top');if(!b)return;window.addEventListener('scroll',function(){b.classList.toggle('is-visible',window.scrollY>420);},{passive:true});b.addEventListener('click',function(){window.scrollTo({top:0,behavior:'smooth'});});}();</script>
<script src="assets/conversion-tracking.js" defer></script>
</body>
</html>
