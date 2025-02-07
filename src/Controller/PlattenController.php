<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Erpag\DBQueryServiceBundle\Service\QueryService;
use App\Service\PlattenService;
use App\Entity\Platte;

class PlattenController extends AbstractController
{
    #[Route('/', name: 'stapel_visualisierung')]
    public function palettenCollectionAction(Request $request, SessionInterface $session, QueryService $qs, PlattenService $ps): Response
    {
        $path = '';

        if (!$session->has('stapel')) {
            $stapel = $ps->initPaletten();
        }
        else{
            $stapel = $session->get('stapel', []);
        }

        // Überprüfen, ob eine Palette geleert werden soll
        $emptyPalette = $request->get('empty_palette');
        if ($emptyPalette) {

            foreach($stapel[$emptyPalette]['stapelplaetze'] as &$stapelplatz)
            {
                $stapelplatz = [];
            }
            $session->set('stapel', $stapel); // Speichern des geänderten Stapels in der Session
        }

        if ($request->isMethod('POST')) {
            $path = $request->get('barcode');
            
            // Neue Platte hinzufügen
            if ($path) {

                $queryUnique = $qs->new()
                ->identifiedBy('Platten', 'is_unique_by_cnc_path')
                ->replace([
                    'CNC_PATH' => $path,
                ])
                ->getResult() ?? [];

                if(count($queryUnique) == 0)
                {
                    $session->getFlashBag()->add('Error', 'Keine Platte mit diesem Code gefunden. Bitte auf Wagen für die Rover packen.');
                    return $this->redirectToRoute('stapel_visualisierung');
                }
                else if(count($queryUnique) > 1)
                {
                    $session->getFlashBag()->add('Error', 'Code auf verschiedenen Platten vorhanden. Größenzuordnung nicht möglich. Bitte auf Wagen für die Rover packen.');
                    return $this->redirectToRoute('stapel_visualisierung');
                }

                $neuePlatte = $qs->new()
                    ->identifiedBy('Platten', 'by_cnc_path')
                    ->replace([
                        'CNC_PATH' => $path,
                    ])
                    ->getResult()[0] ?? null;

                //Wenn Platte gefunden, erstmal prüfen, ob überhaupt für Roboter-CNC verfügbar
                if ($neuePlatte) {
                    if(
                        ($neuePlatte->breite > $this->getParameter('app.plattenbreite_max') && $neuePlatte->laenge > $this->getParameter('app.plattenbreite_max')) ||
                        ($neuePlatte->breite > $this->getParameter('app.plattenlaenge_max')) ||
                        ($neuePlatte->laenge > $this->getParameter('app.plattenlaenge_max'))
                        )
                    {
                        $session->getFlashBag()->add('Error', 'Platte zu groß. Bitte auf Wagen für die Rover packen.');
                        return $this->redirectToRoute('stapel_visualisierung');
                    }

                    //Maximaldicke prüfen
                    if($neuePlatte->dicke > $this->getParameter('app.plattendicke_max'))
                    {
                        $session->getFlashBag()->add('Error', 'Platte zu dick. Bitte auf Wagen für die Rover packen.');
                        return $this->redirectToRoute('stapel_visualisierung');
                    }
                    else if($neuePlatte->dicke < $this->getParameter('app.plattendicke_min'))
                    {
                        $session->getFlashBag()->add('Error', 'Platte zu dünn. Bitte auf Wagen für die Rover packen.');
                        return $this->redirectToRoute('stapel_visualisierung');
                    }

                    //Dann prüfen, ob Platte zu klein
                    if(
                        ($neuePlatte->breite < $this->getParameter('app.plattenbreite_min') || $neuePlatte->laenge < $this->getParameter('app.plattenbreite_min'))||
                        ($neuePlatte->breite > $this->getParameter('app.plattenlaenge_min') && $neuePlatte->laenge < $this->getParameter('app.plattenbreite_min')) ||
                        ($neuePlatte->laenge > $this->getParameter('app.plattenlaenge_min') && $neuePlatte->breite < $this->getParameter('app.plattenbreite_min'))
                    ){
                        $session->getFlashBag()->add('Error', 'Platte zu klein. Bitte auf Wagen für die Rover packen.');
                        return $this->redirectToRoute('stapel_visualisierung');
                    }

                    $platte = new Platte($neuePlatte->breite, $neuePlatte->laenge, $neuePlatte->dicke, $neuePlatte->id, $neuePlatte->kanteLinks, $neuePlatte->kanteOben, $neuePlatte->kanteRechts, $neuePlatte->kanteUnten, false);
                    if(!$ps->optimierteStapelbildung($stapel, $platte))
                    {
                        $session->getFlashBag()->add('Error', 'Kein passender Platz gefunden. Passende Palette bitte leeren oder Platte zwischenlagern.');
                        return $this->redirectToRoute('stapel_visualisierung');
                    }
                    $session->set('stapel', $stapel);
                }
            }

            // Entfernen der letzten Platte
            $removeLastPlatte = $request->get('remove_last');
            if ($removeLastPlatte) {
                $ps->removeLastPlatte($stapel);
                $session->set('stapel', $stapel);
            }

            // Leeren einer Palette
            $clearPalette = $request->get('clear_palette');
            if ($clearPalette) {
                $ps->clearPalette($stapel, $clearPalette);
                $session->set('stapel', $stapel);
            }
        }

        if ($request->query->get('reset')) {
            $session->remove('stapel');
            return $this->redirectToRoute('stapel_visualisierung');
        }

        $letztePlatte = $ps->findLetztePlatte($stapel);

        return $this->render('index.html.twig', [
            'stapelListe' => $stapel,
            'letztePlatte' => $letztePlatte ?? null,
        ]);
    }
}