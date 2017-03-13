<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\AdvancedCampaignReporting\Columns;


use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\AdvancedCampaignReporting\AdvancedCampaignReporting;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;
use Piwik\Tracker\Action;

abstract class Base extends VisitDimension
{
    protected $detectedCampaignParameters = [];

    public function getRequiredVisitFields()
    {
        return array(
            'referer_type',
            'referer_name',
            'referer_keyword'
        );
    }

    protected function detectCampaign(Request $request, Visitor $visitor)
    {
        $campaignDetector   = StaticContainer::get('advanced_campaign_reporting.campaign_detector');
        $campaignParameters = AdvancedCampaignReporting::getCampaignParameters();

        $visitProperties = $visitor->visitProperties->getProperties();

        $campaignDimensions = $campaignDetector->detectCampaignFromRequest(
            $request,
            $campaignParameters
        );

        if (empty($campaignDimensions)) {
            // If for some reason a campaign was detected in Core Tracker
            // but not here, copy that campaign to the Advanced Campaign
            if ($visitProperties['referer_type'] == Common::REFERRER_TYPE_CAMPAIGN) {

                $campaignDimensions = array(
                    (new CampaignName())->getColumnName() => $visitProperties['referer_name']
                );
                if (!empty($visitProperties['referer_keyword'])) {
                    $campaignDimensions[(new CampaignKeyword())->getColumnName()] = $visitProperties['referer_keyword'];
                }
            }
        }

        if (empty($campaignDimensions)) {
            $campaignDimensions = $campaignDetector->detectCampaignFromVisit(
                $visitProperties,
                $campaignParameters
            );
        }

        return $campaignDimensions;
    }

    protected function getCampaignValue($field, Request $request, Visitor $visitor)
    {
        if (empty($detectedCampaignParameters)) {
            $this->detectedCampaignParameters = $this->detectCampaign($request, $visitor);
        }

        if (array_key_exists($field, $this->detectedCampaignParameters)) {
            return substr($this->detectedCampaignParameters[$field], 0, $field == 'campaign_id' ? 100 : 255);
        }

        return null;
    }

    /**
     * @param Request     $request
     * @param Visitor     $visitor
     * @param Action|null $action
     * @return mixed
     */
    public function onNewVisit(Request $request, Visitor $visitor, $action)
    {
        $campaignDetector   = StaticContainer::get('advanced_campaign_reporting.campaign_detector');
        $campaignParameters = AdvancedCampaignReporting::getCampaignParameters();

        $visitProperties = $visitor->visitProperties->getProperties();

        $campaignDimensions = $campaignDetector->detectCampaignFromRequest(
            $request,
            $campaignParameters
        );

        if (empty($campaignDimensions)) {
            // If for some reason a campaign was detected in Core Tracker
            // but not here, copy that campaign to the Advanced Campaign
            if ($visitProperties['referer_type'] == Common::REFERRER_TYPE_CAMPAIGN) {

                $campaignDimensions = array(
                    (new CampaignName())->getColumnName() => $visitProperties['referer_name']
                );
                if (!empty($visitProperties['referer_keyword'])) {
                    $campaignDimensions[(new CampaignKeyword())->getColumnName()] = $visitProperties['referer_keyword'];
                }
            }
        }

        if (array_key_exists($this->getColumnName(), $campaignDimensions)) {
            return substr($campaignDimensions[$this->getColumnName()], 0, $this->getColumnName() == 'campaign_id' ? 100 : 255);
        }

        return null;
    }

    /**
     * @param Request     $request
     * @param Visitor     $visitor
     * @param Action|null $action
     * @return mixed
     */
    public function onAnyGoalConversion(Request $request, Visitor $visitor, $action)
    {
        $campaignDetector   = StaticContainer::get('advanced_campaign_reporting.campaign_detector');
        $campaignParameters = AdvancedCampaignReporting::getCampaignParameters();

        $visitProperties = $visitor->visitProperties->getProperties();

        $campaignDimensions = $campaignDetector->detectCampaignFromVisit(
            $visitProperties,
            $campaignParameters
        );

        if (empty($campaignDimensions)) {
            $campaignDimensions = $campaignDetector->detectCampaignFromRequest(
                $request,
                $campaignParameters
            );
        }

        if (array_key_exists($this->getColumnName(), $campaignDimensions)) {
            return substr($campaignDimensions[$this->getColumnName()], 0, $this->getColumnName() == 'campaign_id' ? 100 : 255);
        }

        return null;
    }
}
