<?php
namespace Magelumen\Security\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\AlreadyExistsException;

class ModelCheck implements ObserverInterface
{
  private $remoteAddress;
  protected $_urlInterface;

  public function __construct(
    \Magento\Framework\App\RequestInterface $request,
    ManagerInterface $messageManager,
    RedirectFactory $resultRedirectFactory,
    RemoteAddress $remoteAddress,
	\Magento\Framework\UrlInterface $urlInterface
  )
  {
    $this->_request = $request;
    $this->messageManager = $messageManager;
    $this->resultRedirectFactory = $resultRedirectFactory;
    $this->remoteAddress = $remoteAddress;
	$this->_urlInterface = $urlInterface;
    
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    $obj = $observer->getEvent()->getObject();
    $error = false;
	
	$currentUrl = $this->_urlInterface->getCurrentUrl();
	$pos = strpos($currentUrl, 'plugincompany_contactforms');

    // $this->remoteAddress->getRemoteAddress()
    // if($obj->getIp()){
        if($this->_request->getPostValue() && is_array($this->_request->getPostValue()) && $pos === false){
            $postData = $this->_request->getPostValue();
            
            foreach ($postData as $key => $value) 
            {
                if(is_string($value))
                {
                    // Converting String into lower case to check if string contains any script tag
                    $value = strtolower($value);
                    if (!empty($value) && ( strpos($value, "script") || strpos($value,'&lt;script') ) ) {
                        // preg_match('/(<script)/', $value, $match);

                        // echo strpos($value,'&lt;script'); die;
                        preg_match('/<script/', (string)$value, $match);
                        $new_str = str_replace(' ', '', (string)$value);
                        preg_match('/<script/', (string)$new_str, $match2);

                        if( strpos($value,'&lt;script') || count($match) || count($match2) ){ 
                            throw new \Magento\Framework\Exception\LocalizedException(__('Script not allowed'));
                            $error = true;
                            break;
                        }
                    }
                }
                if(is_array($value)){
                    foreach ($value as $data) 
                    {
                        if(is_string($data))
                        {
                            // Converting String into lower case to check if string contains any script tag
                            $data = strtolower($data);
                            if (!empty($data)&& ( strpos($data, "script") || strpos($data,'&lt;script') ) ){
                                // preg_match('/<script/', $data, $match);
                                preg_match('/<script/', (string)$data, $match);
                                $new_str = str_replace(' ', '', (string)$data);

                                preg_match('/<script/', (string)$new_str, $match2);

                                if( strpos($data,'&lt;script') || count($match) || count($match2) ){
                                    throw new \Magento\Framework\Exception\LocalizedException(__('Script not allowed'));
                                    $error = true;
                                    break;
                                }
                            }
                        }
                    }
                }

            }
        }
    // }

    if($error){
        $this->messageManager->addError(__('Script not allowed'));
        // $this->_dataSaveAllowed = false;
        // $this->resultRedirectFactory->create()->setPath('*/*/');
        throw new \Magento\Framework\Exception\LocalizedException(__('Script not allowed'));

    }
  }
}