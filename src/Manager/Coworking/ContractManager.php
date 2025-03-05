<?php

namespace App\Manager\Coworking;

use App\Entity\User;
use App\Service\PdfService;
use Twig\Environment as Twig;
use Symfony\Component\Form\Form;
use App\Entity\Coworking\Contract;
use App\Entity\Marketing\Commission;
use App\Entity\BusinessModel\Invoice;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContractManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private Twig $twig,
        private RequestStack $requestStack,
        private Security $security,
        private PdfService $pdfService,
        private UrlGeneratorInterface $urlGeneratorInterface,
    ){}

    public function init(): Contract
    {
        $contract = new Contract();

        return $contract;
    }

    public function save(Contract $contract)
    {
        $this->em->persist($contract);
        $this->em->flush();
    }

    public function saveForm(Form $form)
    {
        /** @var Contract $contract */
        $contract = $form->getData();
        if($contract->getStatus() === Contract::STATUS_VALIDATED){
            if($contract->getAffiliateCode() !== null){
                $affiliateCode = $contract->getAffiliateCode();
                $affiliateBy = $this->em->getRepository(User::class)->findOneByAffiliateCode($affiliateCode);
                
                if($affiliateBy instanceof User){
                    $commission = new Commission();
                    $commission->setUser($affiliateBy);
                    $commission->setSaleReference($contract->getPackage()->getSlug());
                    $commission->setCommissionPercentage(0.05);
                    $commission->setAmount($contract->getPackage()->getPrice() * 0.05);
                    if ($contract->getPackage()->getSlug() === 'vip-coworking') {
                        $commission->setServiceType(Commission::TYPE_SUBSCRIPTION);
                        $commission->setSalesPeriod('Annuel');
                    }
                    $commission->setStatus(Commission::STATUS_PENDING);
                    $commission->setFixedAmount($contract->getPackage()->getPrice() * 0.05);
                    $this->em->persist($commission);
                }
            }
            $contract->setExpiredAt(new \DateTime('+1 month'));
            $contract->setFlexi($contract->getPackage()->getCredit());
            $this->createInvoice($contract);
        }
        $this->save($contract);

        return $contract;
    }

    public function createInvoice(Contract $contract) :void
    {
        $invoice = $contract->getInvoice();
        if(!$invoice instanceof Invoice){
            $invoice = new Invoice();
        }
        $invoice->setTypePerson($contract->isTypePerson());
        $invoice->setSocialReason($contract->getSocialReason());
        $invoice->setLabel($contract->getContractNumber());
        $invoice->setName($contract->getLastName());
        $invoice->setFirstName($contract->getFirstName());
        $invoice->setAdress($contract->getAdress());
        $invoice->setPostalCode($contract->getPostalCode());
        $invoice->setCity($contract->getCity());
        $invoice->setContract($contract);

        $this->em->persist($invoice);
        $this->em->flush();
    }
    
    public function generateFacture(Contract $contract)
    {
		$folder = $contract->getGeneratedDocsPath();
        $file = $contract->getGeneratedFacturePathFile();
        // create directory
        if (!is_dir($folder)) mkdir($folder, 0777, true);
		$scanFolder = scandir($folder);
        if (!in_array("facture.pdf", $scanFolder)) { 
            $snappy = $this->pdfService->createPdf();
            $html = $this->twig->render("coworking/contract/pdf/facture.pdf.twig", [
                'contract' => $contract, 
                'pathToWeb' => $this->urlGeneratorInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

            $output = $snappy->getOutputFromHtml($html);
            
            $filefinal = file_put_contents($file, $output);
        }
        
        return $file;
	}
    
    public function generateContract(Contract $contract)
    {
		$folder = $contract->getGeneratedDocsPath();
        $file = $contract->getGeneratedContractPathFile();
        // create directory
        if (!is_dir($folder)) mkdir($folder, 0777, true);
		$scanFolder = scandir($folder);
        if (!in_array("contrat.pdf", $scanFolder)) { 
            $snappy = $this->pdfService->createPdf();
            $html = $this->twig->render("coworking/contract/pdf/contrat.pdf.twig", [
                'contract' => $contract, 
                'package' => $contract->getPackage()->getSlug(),
                'pathToWeb' => $this->urlGeneratorInterface->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ]);

            $output = $snappy->getOutputFromHtml($html);
            
            $filefinal = file_put_contents($file, $output);
        }
        
        return $file;
	}

    public function checkIfTransactionSuccess(Contract $contract): bool
    {
        if ($contract->getStatus() !== Contract::STATUS_VALIDATED) {
			return false;
		}

		return true;
    }
}