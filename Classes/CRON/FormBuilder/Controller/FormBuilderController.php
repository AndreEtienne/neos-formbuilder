<?php
namespace CRON\FormBuilder\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "CRON.FormBuilder".      *
 *                                                                        *
 *                                                                        */


use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use CRON\FormBuilder\Utils\EmailMessage;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;


class FormBuilderController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;


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
	 * @var NodeInterface $siteNode
	 */
	protected $siteNode = NULL;


	/**
	 * @return void
	 */
	public function indexAction() {
		$this->view->assign('attributes',$this->request->getInternalArgument('__attributes'));
		$this->view->assign('elements',$this->request->getInternalArgument('__elements'));
		$this->view->assign('elementsArray',$this->request->getInternalArgument('__elementsArray'));
		$this->view->assign('documentNode',$this->request->getInternalArgument('__documentNode'));
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function submitAction($data) {

		$siteNode = $this->getSiteNode();

		$fields = [];

		foreach($data as $identifier => $value) {
			$node = $this->nodeDataRepository->findOneByIdentifier($identifier, $siteNode->getWorkspace());

			//we can only handle registered nodes, must be a form manipulation
			if($node === NULL) $this->throwStatus(403);

			$fields[] = array('label' => $node->getProperty('label'), 'value' => $value);
		}

		$this->sendMail($fields);

		$this->redirect('submitPending');
	}

	/**
	 * @return void
	 */
	public function submitPendingAction() {}


	/**
	 * Sends your details to recipient
	 * @param array $fields
	 * @return void
	 */
	private function sendMail($fields) {

		$receiver = explode(',', $this->request->getInternalArgument('__receiver'));

		$emailMessage = new EmailMessage('Form');

		$emailMessage->fluidView->assign('subject', $this->request->getInternalArgument('__subject'));
		$emailMessage->fluidView->assign('fields', $fields);
		$emailMessage->fluidView->setControllerContext($this->controllerContext);
		$emailMessage->send($receiver);
	}

	/**
	 * Get the root site node
	 *
	 * @return \TYPO3\TYPO3CR\Domain\Model\NodeInterface
	 */
	private function getSiteNode() {

		if (!$this->siteNode) {
			$this->siteNode = $this->createContext()->getCurrentSiteNode();
		}

		return $this->siteNode;
	}

	/**
	 * @param string $workspace
	 * @param bool   $showInvisibleAndInaccessibleContent
	 *
	 * @throws \Exception
	 * @return \TYPO3\Neos\Domain\Service\ContentContext
	 */
	private function createContext($workspace = 'live', $showInvisibleAndInaccessibleContent = TRUE) {

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
