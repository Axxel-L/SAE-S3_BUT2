<?php
require_once '../classes/init.php';
require_once '../header.php';
?>

<div class="gaming-bg">
    <div class="diagonal-lines"></div>
    <div class="diagonal-lines-2"></div>
    <div class="diagonal-lines-3"></div>
    <div class="award-grid"></div>
    <div class="trophy-pattern"></div>
    <div class="controller-icons" id="controller-icons"></div>
</div>
<br>
<section class="py-28 px-6">
    <div class="container mx-auto max-w-4xl">
        <div class="text-center mb-16">
            <div class="inline-block mb-8">
                <div class="glass-card rounded-full p-6 w-24 h-24 mx-auto flex items-center justify-center border-2 border-white/10">
                    <i class="fas fa-balance-scale text-4xl accent-gradient"></i>
                </div>
            </div>
            <h1 class="text-5xl md:text-6xl font-bold mb-6 font-orbitron text-light">
                Mentions <span class="accent-gradient">Légales</span>
            </h1>
            <p class="text-xl text-light/70">
                Conformément à la loi n°2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique (LCEN)
            </p>
        </div>

        <div class="space-y-10">
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-building text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Éditeur du site</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p><strong class="text-accent">Nom ou raison sociale :</strong> GameCrown SAS</p>
                    <p><strong class="text-accent">Forme juridique :</strong> Société par Actions Simplifiée</p>
                    <p><strong class="text-accent">Capital social :</strong> 50 000 €</p>
                    <p><strong class="text-accent">Adresse :</strong> 11 Rue de l'Université, 88100 Saint-Dié-des-Vosges, France</p>
                    <p><strong class="text-accent">Téléphone :</strong> +33 6 00 00 00 00</p>
                    <p><strong class="text-accent">Email :</strong> contact@gamecrown.fr</p>
                    <p><strong class="text-accent">Numéro SIRET :</strong> 123 456 789 00012</p>
                    <p><strong class="text-accent">Numéro RCS :</strong> Épinal B 123 456 789</p>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-user-tie text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Directeur de la publication</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p><strong class="text-accent">Nom :</strong> GameCrown SAS</p>
                    <p><strong class="text-accent">Fonction :</strong> Président Directeur Général</p>
                    <p><strong class="text-accent">Email :</strong> no-reply@gamecrown.fr</p>
                    <p><strong class="text-accent">Téléphone :</strong> +33 6 00 00 00 01</p>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-server text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Hébergeur</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p><strong class="text-accent">Nom :</strong> NeoHeberg</p>
                    <p><strong class="text-accent">Adresse :</strong> 27-25 Rue des Boulangers</p>
                    <p><strong class="text-accent">Site web :</strong> www.neoheberg.fr</p>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-shield-alt text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Protection des données personnelles</h2>
                </div>
                <div class="space-y-6 text-light/80 text-lg">
                    <div>
                        <h3 class="text-xl font-bold text-light mb-2">Responsable du traitement</h3>
                        <p>GameCrown SAS, dont les coordonnées sont indiquées ci-dessus.</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-light mb-2">Finalités du traitement</h3>
                        <ul class="list-disc pl-6 space-y-2">
                            <li>Gestion des inscriptions et comptes utilisateurs</li>
                            <li>Organisation et gestion des votes électroniques</li>
                            <li>Communication relative aux événements et résultats</li>
                            <li>Amélioration des services proposés</li>
                            <li>Respect des obligations légales et réglementaires</li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-light mb-2">Droits des personnes</h3>
                        <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez des droits suivants :</p>
                        <ul class="list-disc pl-6 space-y-2 mt-2">
                            <li>Droit d'accès à vos données personnelles</li>
                            <li>Droit de rectification des données inexactes</li>
                            <li>Droit à l'effacement ("droit à l'oubli")</li>
                            <li>Droit à la limitation du traitement</li>
                            <li>Droit à la portabilité des données</li>
                            <li>Droit d'opposition au traitement</li>
                        </ul>
                        <p class="mt-4">Pour exercer ces droits, contactez notre Délégué à la Protection des Données (DPO).</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-light mb-2">Délégué à la Protection des Données (DPO)</h3>
                        <p><strong class="text-accent">Nom :</strong> Marie Laurent</p>
                        <p><strong class="text-accent">Email :</strong> dpo@gamecrown.fr</p>
                        <p><strong class="text-accent">Adresse postale :</strong> Service DPO, GameCrown SAS, 11 Rue de l'Université, 88100 Saint-Dié-des-Vosges</p>
                    </div>
                    
                    <div>
                        <h3 class="text-xl font-bold text-light mb-2">Conservation des données</h3>
                        <p>Les données personnelles sont conservées pour la durée nécessaire à la réalisation des finalités pour lesquelles elles sont collectées, conformément aux prescriptions légales.</p>
                    </div>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-copyright text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Propriété intellectuelle</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p>L'ensemble de ce site relève de la législation française et internationale sur le droit d'auteur et la propriété intellectuelle.</p>
                    <p>Tous les droits de reproduction sont réservés, y compris pour les documents téléchargeables et les représentations iconographiques et photographiques.</p>
                    <p>La marque "GameCrown" et le logo associé sont des marques déposées de GameCrown SAS.</p>
                    <p>Les images de jeux vidéo présentes sur le site sont utilisées à des fins d'illustration et restent la propriété de leurs éditeurs respectifs.</p>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-exclamation-triangle text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Limitation de responsabilité</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p>GameCrown SAS ne peut garantir l'exactitude, l'exhaustivité ou l'actualité des informations diffusées sur son site.</p>
                    <p>L'utilisateur reconnaît utiliser ces informations sous sa responsabilité exclusive.</p>
                    <p>GameCrown SAS décline toute responsabilité pour les dommages directs ou indirects pouvant résulter de l'accès ou de l'utilisation du site.</p>
                    <p>Le site peut contenir des liens vers d'autres sites. GameCrown SAS n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu.</p>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-cookie-bite text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Politique relative aux cookies</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p>Le site GameCrown utilise des cookies pour :</p>
                    <ul class="list-disc pl-6 space-y-2">
                        <li>Assurer le bon fonctionnement du site</li>
                        <li>Mémoriser vos préférences de connexion</li>
                        <li>Analyser l'audience du site (cookies Google Analytics)</li>
                        <li>Sécuriser le processus de vote</li>
                    </ul>
                    <p class="mt-4">En naviguant sur notre site, vous acceptez l'utilisation de ces cookies. Vous pouvez les désactiver via les paramètres de votre navigateur.</p>
                </div>
            </div>
            <div class="glass-card rounded-[2rem] p-8 border-2 border-white/10 fade-in">
                <div class="flex items-center mb-6">
                    <div class="glass-button rounded-[1rem] p-3 mr-4 border border-white/10">
                        <i class="fas fa-gavel text-accent"></i>
                    </div>
                    <h2 class="text-3xl font-bold font-orbitron text-light">Déclaration CNIL</h2>
                </div>
                <div class="space-y-4 text-light/80 text-lg">
                    <p>Les traitements de données personnelles réalisés sur le site GameCrown ont fait l'objet d'une déclaration auprès de la Commission Nationale de l'Informatique et des Libertés (CNIL) sous le numéro : <strong class="text-accent">2156798</strong>.</p>
                    <p>Pour toute réclamation concernant la protection de vos données personnelles, vous pouvez vous adresser à la CNIL :</p>
                    <p class="pl-6">3 Place de Fontenoy<br>TSA 80715<br>75334 Paris Cedex 07<br>Tél : 01 53 73 22 22</p>
                </div>
            </div>
        </div>

        <div class="mt-12 text-center text-light/60">
            <p><i class="fas fa-calendar-alt text-accent mr-2"></i> Dernière mise à jour : <?php echo date('d/m/Y'); ?></p>
            <p class="mt-2 text-sm">Ces mentions légales sont susceptibles d'être modifiées. Nous vous invitons à les consulter régulièrement.</p>
        </div>
    </div>
</section>