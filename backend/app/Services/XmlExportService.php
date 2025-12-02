<?php

namespace App\Services;

use App\Models\PointOfSale;
use SimpleXMLElement;

class XmlExportService
{
    /**
     * Export Point of Sales data to XML format
     */
    public function exportToXml($query = null): string
    {
        $pdvs = $query ?? PointOfSale::where('status', 'validated')
            ->with(['organization', 'creator'])
            ->get();
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><pointsOfSale/>');
        
        foreach ($pdvs as $pdv) {
            $node = $xml->addChild('pdv');
            
            // Basic info
            $node->addChild('id', $pdv->id);
            $node->addChild('numero', $pdv->numero);
            $node->addChild('status', $pdv->status);
            
            // Dealer info
            $dealer = $node->addChild('dealer');
            $dealer->addChild('name', htmlspecialchars($pdv->dealer_name));
            $dealer->addChild('numero_flooz', htmlspecialchars($pdv->numero_flooz));
            $dealer->addChild('shortcode', htmlspecialchars($pdv->shortcode));
            $dealer->addChild('organization', htmlspecialchars($pdv->organization->name ?? ''));
            
            // PDV info
            $pdvInfo = $node->addChild('pdv_info');
            $pdvInfo->addChild('nom_point', htmlspecialchars($pdv->nom_point));
            $pdvInfo->addChild('profil', htmlspecialchars($pdv->profil));
            $pdvInfo->addChild('type_activite', htmlspecialchars($pdv->type_activite));
            
            // Manager info
            $manager = $node->addChild('manager');
            $manager->addChild('firstname', htmlspecialchars($pdv->firstname));
            $manager->addChild('lastname', htmlspecialchars($pdv->lastname));
            $manager->addChild('date_of_birth', $pdv->date_of_birth?->format('Y-m-d'));
            $manager->addChild('gender', $pdv->gender);
            $manager->addChild('id_number', htmlspecialchars($pdv->id_number));
            $manager->addChild('nationality', htmlspecialchars($pdv->nationality));
            $manager->addChild('profession', htmlspecialchars($pdv->profession));
            
            // Location
            $location = $node->addChild('location');
            $location->addChild('region', $pdv->region);
            $location->addChild('prefecture', htmlspecialchars($pdv->prefecture));
            $location->addChild('commune', htmlspecialchars($pdv->commune));
            $location->addChild('canton', htmlspecialchars($pdv->canton));
            $location->addChild('ville', htmlspecialchars($pdv->ville));
            $location->addChild('quartier', htmlspecialchars($pdv->quartier));
            $location->addChild('localisation', htmlspecialchars($pdv->localisation));
            
            // GPS coordinates
            $gps = $node->addChild('gps');
            $gps->addChild('latitude', $pdv->latitude);
            $gps->addChild('longitude', $pdv->longitude);
            $gps->addChild('accuracy', $pdv->gps_accuracy);
            
            // Contacts
            $contacts = $node->addChild('contacts');
            $contacts->addChild('numero_proprietaire', htmlspecialchars($pdv->numero_proprietaire));
            $contacts->addChild('autre_contact', htmlspecialchars($pdv->autre_contact));
            
            // Fiscal info
            $fiscal = $node->addChild('fiscal');
            $fiscal->addChild('nif', htmlspecialchars($pdv->nif));
            $fiscal->addChild('regime_fiscal', htmlspecialchars($pdv->regime_fiscal));
            
            // Visibility
            $visibility = $node->addChild('visibility');
            $visibility->addChild('support_visibilite', htmlspecialchars($pdv->support_visibilite));
            $visibility->addChild('etat_support', $pdv->etat_support);
            
            // Other
            $node->addChild('numero_cagnt', htmlspecialchars($pdv->numero_cagnt));
            
            // Validation info
            if ($pdv->validated_at) {
                $validation = $node->addChild('validation');
                $validation->addChild('validated_at', $pdv->validated_at->format('Y-m-d H:i:s'));
                $validation->addChild('validated_by', htmlspecialchars($pdv->validator->name ?? ''));
            }
            
            // Creation info
            $creation = $node->addChild('creation');
            $creation->addChild('created_at', $pdv->created_at->format('Y-m-d H:i:s'));
            $creation->addChild('created_by', htmlspecialchars($pdv->creator->name ?? ''));
        }
        
        return $xml->asXML();
    }
}
