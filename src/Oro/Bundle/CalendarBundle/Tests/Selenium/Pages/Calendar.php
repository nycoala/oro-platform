<?php

namespace Oro\Bundle\CalendarBundle\Tests\Selenium\Pages;

use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Calendar
 *
 * @package Oro\Bundle\CalendarBundle\Tests\Selenium\Pages
 * @method Calendar openCalendar() openCalendar(string)
 * {@inheritdoc}
 */
class Calendar extends Calendars
{
    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->$title = $this->test->byId('oro_calendar_event_form_title');
        $this->$title->clear();
        $this->$title->value($title);

        return $this;
    }

    /**
     * @param $date string Start date
     * @return $this
     * @throws \Exception
     */
    public function setStartDate($date)
    {
        $startDate = $this->test->byId('date_selector_oro_calendar_event_form_start');
        $startTime = $this->test->byId('time_selector_oro_calendar_event_form_start');
        $startDate->clear();
        $startTime->clear();
        if (preg_match('/^(.+)\s(\d{2}\:\d{2}\s\w{2})$/', $date, $date)) {
            $this->test->execute(
                array(
                    'script' => "$('#date_selector_oro_calendar_event_form_start').val('$date[1]');" .
                        "$('#date_selector_oro_calendar_event_form_start').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
            $this->test->execute(
                array(
                    'script' => "$('#time_selector_oro_calendar_event_form_start').val('$date[2]');" .
                        "$('#time_selector_oro_calendar_event_form_start').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
        } else {
            throw new Exception("Value {$date} is not a valid date");
        }

        return $this;
    }

    /**
     * @param $date string End date
     * @return $this
     * @throws \Exception
     */
    public function setEndDate($date)
    {
        $startDate = $this->test->byId('date_selector_oro_calendar_event_form_end');
        $startTime = $this->test->byId('time_selector_oro_calendar_event_form_end');
        $startDate->clear();
        $startTime->clear();
        if (preg_match('/^(.+)\s(\d{2}\:\d{2}\s\w{2})$/', $date, $date)) {
            $this->test->execute(
                array(
                    'script' => "$('#date_selector_oro_calendar_event_form_end').val('$date[1]');" .
                        "$('#date_selector_oro_calendar_event_form_end').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
            $this->test->execute(
                array(
                    'script' => "$('#time_selector_oro_calendar_event_form_end').val('$date[2]');" .
                        "$('#time_selector_oro_calendar_event_form_end').trigger('change').trigger('blur')",
                    'args' => array()
                )
            );
        } else {
            throw new \Exception("Value {$date} is not a valid date");
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function saveEvent()
    {
        $this->test->byXpath("//button[@type='submit'][normalize-space(.)='Save']")->click();
        $this->waitForAjax();
        $this->assertElementNotPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]",
            'Event window is still open'
        );

        return $this;
    }

    /**
     * @return $this
     */
    public function deleteEvent()
    {
        $this->test->byXpath(
            "//div[@class='widget-actions-section']//a[@title[normalize-space(.)='Delete event']]"
        )->click();
        $this->test->byXpath(
            "//div[@class='modal oro-modal-danger in']//a[normalize-space(.)='Yes, Delete']"
        )->click();
        $this->waitForAjax();
        $this->assertElementNotPresent(
            "//div[contains(@class,'ui-dialog-titlebar')]",
            'Event window is still open'
        );

        return $this;
    }
}
