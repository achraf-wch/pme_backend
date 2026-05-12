<?php

namespace Database\Seeders;

use App\Models\StaticPage;
use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'about',
                'title' => 'A propos du PME',
                'content' => "Le Parti du Maroc Emergent porte une vision politique moderne, responsable et proche des citoyens.\n\nCette page peut etre modifiee depuis le tableau de bord par l'administration centrale ou le superviseur.",
                'meta_title' => 'A propos du PME',
                'meta_description' => 'Presentation du parti, de sa vision et de son engagement public.',
            ],
            [
                'slug' => 'program',
                'title' => 'Programme',
                'content' => "Notre programme met l'accent sur la formation, l'emploi, la participation citoyenne, la transformation numerique et la justice sociale.\n\nLes priorites peuvent etre mises a jour depuis l'espace d'administration.",
                'meta_title' => 'Programme du PME',
                'meta_description' => 'Les grandes priorites politiques et sociales du PME.',
            ],
            [
                'slug' => 'privacy',
                'title' => 'Politique de confidentialite',
                'content' => "Nous respectons la confidentialite des utilisateurs.\n\nCette page explique comment les donnees personnelles sont collectees, utilisees et protegees sur la plateforme.",
                'meta_title' => 'Politique de confidentialite',
                'meta_description' => 'Informations sur la protection des donnees personnelles.',
            ],
            [
                'slug' => 'terms',
                'title' => 'Conditions d’utilisation',
                'content' => "Ces conditions definissent les regles d'utilisation du site, de l'espace membre, des demandes d'adhesion, des inscriptions aux evenements et des services numeriques.",
                'meta_title' => 'Conditions d’utilisation',
                'meta_description' => 'Regles et conditions d’utilisation de la plateforme.',
            ],
            [
                'slug' => 'faq',
                'title' => 'Questions frequentes',
                'content' => "Retrouvez ici les reponses aux questions courantes sur l'inscription, l'adhesion, le benevolat, les dons, les evenements et les votes internes.",
                'meta_title' => 'FAQ',
                'meta_description' => 'Questions frequentes sur les services et espaces du PME.',
            ],
            [
                'slug' => 'accessibility',
                'title' => 'Accessibilite',
                'content' => "La plateforme vise a offrir une navigation claire, lisible et accessible au plus grand nombre.\n\nLes ameliorations d'accessibilite peuvent etre suivies et mises a jour ici.",
                'meta_title' => 'Accessibilite',
                'meta_description' => 'Declaration d’accessibilite de la plateforme.',
            ],
        ];

        foreach ($pages as $page) {
            StaticPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
