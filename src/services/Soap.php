<?php
/**
 * Imageshop plugin for Craft CMS 3.x
 *
 * Integrate with an Imageshop account and use Imageshop resources in Craft
 *
 * @link      https://vangenplotz.no/
 * @copyright Copyright (c) 2018 Vangen & Plotz AS
 */

namespace vangenplotz\imageshop\services;

use vangenplotz\imageshop\Imageshop;

use Craft;
use craft\base\Component;

/**
 * Soap Service
 *
 * All of your plugin’s business logic should go in services, including saving data,
 * retrieving data, etc. They provide APIs that your controllers, template variables,
 * and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 *
 * @author    Vangen & Plotz AS
 * @package   Imageshop
 * @since     0.0.1
 */
class Soap extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->soap->interfaces()
     *
     * @return mixed
     */
    public function interfaces($token = '')
    {
        if( empty($token) ) {
            $settings = Imageshop::$plugin->settings;
            $token = $settings->token;
        }

        // If no token is sent or set in settings, return null
        if( empty($token) ) {
            return null;
        }

        $action = 'http://imageshop.no/V4/GetInterfaces';

        $xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $xml .= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\n";
        $xml .= "  <soap:Body>\n";
        $xml .= "    <GetInterfaces xmlns=\"http://imageshop.no/V4\">\n";
        $xml .= "      <token>" . $token . "</token>\n";
        $xml .= "    </GetInterfaces>\n";
        $xml .= "  </soap:Body>\n";
        $xml .= "</soap:Envelope>";

        return $this->_request($action, $xml);
    }

    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->soap->getImage()
     *
     * @return mixed
     */
    public function getImage($documentId)
    {
        $settings = Imageshop::$plugin->settings;
        $token = $settings->token;
        $action = 'http://imageshop.no/V4/CreatePermaLinkFromDocumentId';

        $width = 100;
        $height = 100;
        
        $xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $xml .= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\n";
        $xml .= "  <soap:Body>\n";
        $xml .= "    <CreatePermaLinkFromDocumentId xmlns=\"http://imageshop.no/V4\">\n";
        $xml .= "      <token>" . $token . "</token>\n";
        $xml .= "      <documentid>" . $documentId . "</documentid>\n";
        $xml .= "      <width>" . $width . "</width>\n";
        $xml .= "      <height>" . $height . "</height>\n";
        $xml .= "    </CreatePermaLinkFromDocumentId>\n";
        $xml .= "  </soap:Body>\n";
        $xml .= "</soap:Envelope>";

        return $this->_request($action, $xml);
    }


    /**
     * This function can literally be anything you want, and you can have as many service
     * functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     Imageshop::$plugin->soap->search()
     *
     * @return mixed
     */
    public function search($query = '', $documentType = 'ALL')
    {
        $settings = Imageshop::$plugin->settings;
        $token = $settings->token;
        $interfaceName = $settings->interfaceName;

        $action = 'http://imageshop.no/V4/Search';

        $xml  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        $xml .= "<soap:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap=\"http://schemas.xmlsoap.org/soap/envelope/\">\n";
        $xml .= "  <soap:Body>\n";
        $xml .= "    <Search xmlns=\"http://imageshop.no/V4\">\n";
        $xml .= "      <token>" . $token . "</token>\n";
        $xml .= "      <interfacename>" . $interfaceName . "</interfacename>\n";
        $xml .= "      <language>no</language>\n";
        $xml .= "      <querystring>" . $query . "</querystring>\n";
        $xml .= "      <documentType>" . $documentType . "</documentType>\n";
        $xml .= "    </Search>\n";
        $xml .= "  </soap:Body>\n";
        $xml .= "</soap:Envelope>";

        return $this->_request($action, $xml);
    }


    // Private Methods
    // =========================================================================

    /**
     * @return xml
     */
    private function _request($action, $xml)
    {
        $url = 'https://webservices.imageshop.no/V4.asmx';

        $headers = [
            'POST /V4.asmx HTTP/1.1',
            'Host: webservices.imageshop.no',
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($xml),
            'SOAPAction: ' . $action
        ];

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($curl); 
        curl_close($curl);

        $response1 = str_replace("<soap:Body>", "", $response);
        $response2 = str_replace("</soap:Body>", "", $response1);

        return simplexml_load_string($response2);
    }


}
