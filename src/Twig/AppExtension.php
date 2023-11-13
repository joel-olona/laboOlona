<?php

namespace App\Twig;

use DateTime;
use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Entity\Application;
use App\Entity\Entreprise\JobListing;
use App\Entity\EntrepriseProfile;
use App\Entity\Posting;
use App\Entity\User;
use App\Repository\AccountRepository;
use Twig\Extension\AbstractExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private Security $security,
        )
    {
    }
    
    public function getFilters(): array
    {
        return [
            new TwigFilter('status_label', [$this, 'statusLabel']),
            new TwigFilter('posting_status_Label', [$this, 'postingStatusLabel']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('meta_title', [$this, 'metaTitle']),
            new TwigFunction('dashboard_title', [$this, 'dashboardTitle']),
            new TwigFunction('identity_title', [$this, 'identityTitle']),
            new TwigFunction('meta_description', [$this, 'metaDescription']),
            new TwigFunction('meta_keywords', [$this, 'metaKeywords']),
            new TwigFunction('show_account_desc', [$this, 'showAccountDesc']),
            new TwigFunction('isoToEmoji', [$this, 'isoToEmoji']),
            new TwigFunction('show_country', [$this, 'showCountry']),
            new TwigFunction('experience_text', [$this, 'getExperienceText']),
            new TwigFunction('date_difference', [$this, 'dateDifference']),
            new TwigFunction('years_difference', [$this, 'yearsDifference']),
            new TwigFunction('status_label', [$this, 'statusLabel']),
            new TwigFunction('account_label', [$this, 'accountLabel']),
            new TwigFunction('getStatuses', [$this, 'getStatuses']),
        ];
    }



    public function getStatuses(string $status = NULL): string
    {
        $statuses = [
            JobListing::STATUS_DRAFT => 'Bruillon',
            JobListing::STATUS_PUBLISHED => 'Publiée',
            JobListing::STATUS_PENDING => 'En attente de modération',
            JobListing::STATUS_REJECTED => 'Rejetée',
            JobListing::STATUS_EXPIRED => 'Expirée',
            JobListing::STATUS_ARCHIVED => 'Archivée',
            JobListing::STATUS_UNPUBLISHED => 'Non publiée',
            JobListing::STATUS_DELETED => 'Effacée',
            JobListing::STATUS_FEATURED => 'Mis en avant',
            JobListing::STATUS_RESERVED => 'Réservée',
        ];

        return $statuses[$status];
    }

    public function getEntrepriseStatuses(string $status = NULL): string
    {
        $statuses = [
            EntrepriseProfile::STATUS_VALID => 'Valide',
            EntrepriseProfile::STATUS_PREMIUM => 'Premium',
            EntrepriseProfile::STATUS_PENDING => 'En attente',
            EntrepriseProfile::STATUS_BANNED => 'Banni',
        ];

        return $statuses[$status];
    }

    public function accountLabel(string $account): string
    {
        switch ($account) {
            case User::ACCOUNT_CANDIDAT :
                return 'JE CHERCHE UN PROJET';
            case User::ACCOUNT_ENTREPRISE :
                return 'J\'AI DES PROJETS';
            default:
                return '<i class="h6 bi mx-2 bi-circle-fill small text-warning"></i>';
        }
    }

    public function statusLabel(string $status)
    {
        $labels = [
            JobListing::STATUS_DRAFT  => 'Bruillon',
            JobListing::STATUS_PUBLISHED  => 'Publiée',
            JobListing::STATUS_PENDING  => 'En attente',
            JobListing::STATUS_REJECTED  => 'Rejetée',
            JobListing::STATUS_EXPIRED  => 'Expirée',
            JobListing::STATUS_ARCHIVED  => 'Archivée',
            JobListing::STATUS_UNPUBLISHED  => 'Non publiée',
            JobListing::STATUS_DELETED  => 'Effacée',
            JobListing::STATUS_FEATURED  => 'Mis en avant',
            JobListing::STATUS_RESERVED  => 'Réservée',
        ];

        return $labels[$status];
    }

    public function metaTitle(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 

        return $this->translator->trans($routeName . '.title');
    }

    public function dashboardTitle(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 
        
        /** @var User $user */
        $user = $this->security->getUser();
        $name = $user->getPrenom();
        if($user->getEntrepriseProfile() instanceof EntrepriseProfile){
            $name = $user->getEntrepriseProfile()->getNom();
        }

        return $this->translator->trans($routeName . '.dashboard_title', ['%company_name%' => $name]);
    }

    public function identityTitle(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 
        $user = $this->security->getUser();

        return $this->translator->trans($routeName . '.identity_title');
    }

    public function metaDescription(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route'); 
        return $this->translator->trans($routeName . '.description');
    }

    public function metaKeywords(): string
    {
        $routeName = $this->requestStack->getCurrentRequest()->attributes->get('_route');  
        return $this->translator->trans($routeName . '.keywords');
    }

    public function showAccountDesc($accountId)
    {
        $accountId = (int) $accountId;
        $account = $this->accountRepository->findOneById($accountId);
        // dd($account, $account->getDescription());
        return $account;
    }

    public function isoToEmoji(string $code)
    {
        return implode(
            '',
            array_map(
                fn ($letter) => mb_chr(ord($letter) % 32 + 0x1F1E5),
                str_split($code)
            )
        );
    }

    public function showCountry($countryCode)
    {
        if(null !== $countryCode){
            return \Symfony\Component\Intl\Countries::getName($countryCode);
        }
        return null;
    }

    public function getExperienceText(string $value): string
    {
        $choices = [
            'SM' => '1 an',
            'MD' => '1-3 ans',
            'LG' => '3-5 ans',
            'XL' => '+ de 5 ans', // J'ai modifié la clé ici de 'LG' à 'XL' car 'LG' était dupliqué
        ];

        return $choices[$value] ?? 'N/A';
    }
    
    public function dateDifference(DateTime $date1, DateTime $date2): string
    {
        $interval = $date1->diff($date2);

        $result = '';

        if ($interval->y > 0) {
            $result .= $interval->y . ' années ';
        }
        if ($interval->m > 0) {
            $result .= $interval->m . ' mois ';
        }
        // if ($interval->d > 0) {
        //     $result .= $interval->d . ' jours';
        // }

        return trim($result);
    }

    public function yearsDifference(DateTime $date1, DateTime $date2): string
    {
        $interval = $date1->diff($date2);

        $result = '';

        if ($interval->y > 0) {
            $result .= $interval->y . ' années ';
        }

        return trim($result);
    }
}