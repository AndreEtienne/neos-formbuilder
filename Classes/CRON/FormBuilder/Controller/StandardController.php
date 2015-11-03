<?php
namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.FormBuilder".      *
 *                                                                        *
 *                                                                        */


use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;


class StandardController extends \TYPO3\Flow\Mvc\Controller\ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;


	/**
	 * A standalone template view
	 *
	 * @Flow\Inject
	 * @var \TYPO3\Fluid\View\StandaloneView
	 */
	protected $standaloneView;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;


	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @var Node $siteNode
	 */
	public $siteNode = NULL;


	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('elements',$this->request->getInternalArgument('__elements'));

		$legend = $this->request->getInternalArgument('__legend');
		$this->view->assign('legend', $legend);
	}

	/**
	 * Get the root site node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	public function getSiteNode() {

		if (!$this->siteNode) {

			/** @var ContentContext $contentContext */
			$contentContext = $this->createContext();
			$this->siteNode = $contentContext->getCurrentSiteNode();
		}

		return $this->siteNode;
	}

	/**
	 * @return void
	 */
	public function transferAction() {


		$siteNode = $this->getSiteNode();
		$values = $this->request->getArguments();
		$hideFields = array('CRON.FormBuilder:SubmitButton');
		$nodes = [];

		foreach($values as $identifier => $value) {
			$node = $this->nodeDataRepository->findOneByIdentifier($identifier, $siteNode->getWorkspace());
			if(!in_array($node->getNodeType(), $hideFields)){
				$nodes[] = array($node->getProperty('label'), $value);
			}
		}

		$this->sendMail($nodes);
	}

	/**
	 * Sends your details to recipient
	 * @param array $nodes
	 * @return void
	 */
	public function sendMail($nodes) {
		// set your template path

		$emailBody = "neue Nachricht\n\n";

		for($i = 0; $i < count ($nodes); $i++){

			$emailBody .= $nodes[$i][0] . ': '. $nodes[$i][1] . "\n";
		}

		$template = new \TYPO3\Fluid\View\StandaloneView();
		$template->setTemplatePathAndFilename('resource://CRON.FormBuilder/Private/Templates/EMails/Form.html');
		$template->assign('email', $emailBody);





		/*
		$templatepath =  'resource://CRON.FormBuilder/Private/Templates/EMails/Form.html';
		$this->standaloneView->setFormat('html');
		$this->standaloneView->setTemplatePathAndFilename($templatepath);
		$this->standaloneView->assign('email', $emailBody);
		$emailBody = $this->standaloneView->render();
		*/
		// create instance of \TYPO3\SwiftMailer\Message() and set mail details
		$mail = new \TYPO3\SwiftMailer\Message();
		$mail->setFrom('im1705@hotmail.com', 'Benedikt Kastl')
		     ->setTo('im@cron.eu', 'Ingo Mueller')
		     ->setSubject('Your Subject')
			 ->addPart($emailBody,'text/plain','utf-8');
		$mail->send();
	}

	/**
	 * @param string $workspace
	 * @param bool   $showInvisibleAndInaccessibleContent
	 *
	 * @throws \Exception
	 * @return \TYPO3\Neos\Domain\Service\ContentContext
	 */
	public function createContext($workspace = 'live', $showInvisibleAndInaccessibleContent = TRUE) {

		/** @var Site $currentSite */
		$currentSite = $this->siteRepository->findFirstOnline();
		if ($currentSite === NULL) {
			throw new \Exception('no online site available');
		}

		return $this->contextFactory->create([
			'workspaceName'            => $workspace,
			'currentSite'              => $currentSite,
			'invisibleContentShown'    => $showInvisibleAndInaccessibleContent,
			'inaccessibleContentShown' => $showInvisibleAndInaccessibleContent
		]);
	}

}