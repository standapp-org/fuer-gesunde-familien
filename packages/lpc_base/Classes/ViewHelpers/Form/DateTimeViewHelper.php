<?php
namespace LPC\LpcBase\ViewHelpers\Form;

use LPC\LpcBase\Domain\Type\Date;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;

class DateTimeViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
	protected $tagName = 'input';

	public PageRenderer $pageRenderer;

	/**
	 * @var string[]
	 */
	private array $flatpickrOptions = [];

	public function injectPageRenderer(PageRenderer $pageRenderer): void {
		$this->pageRenderer = $pageRenderer;
	}

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerArgument('required','boolean','marks input as mandatory');
		$this->registerTagAttribute('readonly', 'string', 'The readonly attribute of the input field');
		$this->registerTagAttribute('disabled', 'string', 'The disabled attribute of the input field');
		$this->registerTagAttribute('placeholder', 'string', 'The inputs placeholder');

		$this->registerFlatpickrOption('allowInput','boolean','Allows the user to enter a date directly input the input field.',false,true);
		$this->registerFlatpickrOption('altFormat','string','Exactly the same as date format, but for the altInput field');
		$this->registerFlatpickrOption('altInput','boolean','Show the user a readable date (as per altFormat), but return something totally different to the server.');
		$this->registerFlatpickrOption('altInputClass','string','This class will be added to the input element created by the altInput option.  Note that altInput already inherits classes from the original input.');
		$this->registerFlatpickrOption('animate','boolean','Whether to enable animations, such as month transitions');
		$this->registerFlatpickrOption('appendTo','HTMLElement','Instead of body, appends the calendar to the specified node instead.');
		$this->registerFlatpickrOption('ariaDateFormat','string','Defines how the date will be formatted in the aria-label for calendar days, using the same tokens as dateFormat. If you change this, you should choose a value that will make sense if a screen reader reads it out loud. Defaults to "F j, Y"');
		$this->registerFlatpickrOption('clickOpens','boolean','Whether clicking on the input should open the picker. Set it to false if you only want to open the calendar programmatically');
		$this->registerFlatpickrOption('closeOnSelect','boolean','Whether calendar should close after date selection');
		$this->registerFlatpickrOption('conjunction','string','If "mode" is "multiple", this string will be used to join selected dates together for the date input value.');
		$this->registerFlatpickrOption('dateFormat','string','A string of characters which are used to define how the date will be displayed in the input box. See https://chmln.github.io/flatpickr/formatting');
		$this->registerFlatpickrOption('defaultDate','DateOption | DateOption[]','The initial selected date(s).');
		$this->registerFlatpickrOption('defaultHour','int','Initial value of the hour element, when no date is selected');
		$this->registerFlatpickrOption('defaultMinute','int','Initial value of the minute element, when no date is selected');
		$this->registerFlatpickrOption('defaultSeconds','int','Initial value of the seconds element, when no date is selected');
		$this->registerFlatpickrOption('disable','DateLimit<DateOption>[]','Disables certain dates, preventing them from being selected. See https://chmln.github.io/flatpickr/examples/#disabling-specific-dates');
		$this->registerFlatpickrOption('disableMobile','boolean','Set this to true to always use the non-native picker on mobile devices. By default, Flatpickr utilizes native datetime widgets unless certain options (e.g. disable) are used.');
		$this->registerFlatpickrOption('enable','DateLimit<DateOption>[]','Disables all dates except these specified. See https://chmln.github.io/flatpickr/examples/#disabling-all-dates-except-select-few');
		$this->registerFlatpickrOption('enableSeconds','boolean','Enables seconds selection in the time picker.');
		$this->registerFlatpickrOption('enableTime','boolean','Enables the time picker');
		$this->registerFlatpickrOption('hourIncrement','integer','Adjusts the step for the hour input (incl. scrolling)');
		$this->registerFlatpickrOption('inline','boolean','Displays the calendar inline');
		$this->registerFlatpickrOption('locale','string','The locale as a string (e.g. "ru", "en"). See https://chmln.github.io/flatpickr/localization/');
		$this->registerFlatpickrOption('maxDate','DateOption','The maximum date that a user can pick to (inclusive).');
		$this->registerFlatpickrOption('maxTime','DateOption','The maximum time that a user can pick to (inclusive).');
		$this->registerFlatpickrOption('minDate','DateOption','The minimum date that a user can start picking from (inclusive).');
		$this->registerFlatpickrOption('minTime','DateOption','The minimum time that a user can start picking from (inclusive).');
		$this->registerFlatpickrOption('minuteIncrement','integer','Adjusts the step for the minute input (incl. scrolling) Defaults to 5');
		$this->registerFlatpickrOption('mode','"single" | "multiple" | "range" | "time"','Date selection mode, defaults to "single"');
		$this->registerFlatpickrOption('nextArrow','string','HTML for the right arrow icon, used to switch months.');
		$this->registerFlatpickrOption('noCalendar','boolean','Hides the day selection in calendar. Use it along with "enableTime" to create a time picker.');
		$this->registerFlatpickrOption('now','DateOption','');
		$this->registerFlatpickrOption('plugins','Plugin[]','Plugins. See https://chmln.github.io/flatpickr/plugins/');
		$this->registerFlatpickrOption('position','string','"auto" | "above" | "below". How the calendar should be positioned with regards to the input. Defaults to "auto"');
		$this->registerFlatpickrOption('positionElement','Element','The element off of which the calendar will be positioned. Defaults to the date input');
		$this->registerFlatpickrOption('prevArrow','string','HTML for the left arrow icon, used to switch months.');
		$this->registerFlatpickrOption('shorthandCurrentMonth','boolean','Whether to display the current month name in shorthand mode, e.g. "Sep" instead "September"');
		$this->registerFlatpickrOption('static','boolean','Creates a wrapper to position the calendar. Use this if the input is inside a scrollable element');
		$this->registerFlatpickrOption('showMonths','int','');
		$this->registerFlatpickrOption('time_24hr','boolean','Displays time picker in 24 hour mode without AM/PM selection when enabled.',false,true);
		$this->registerFlatpickrOption('weekNumbers','boolean','Display week numbers left of the calendar.');
		$this->registerFlatpickrOption('wrap','boolean','See https://chmln.github.io/flatpickr/examples/#flatpickr-external-elements');
		$this->registerFlatpickrOption('forceFormat','boolen','Use the dateFormat for client side validation');
	}

	/**
	 * @param mixed $default
	 */
	private function registerFlatpickrOption(string $name, string $type, string $description, bool $required = false, $default = null): void {
		$this->registerArgument($name, $type, $description, $required, $default);
		$this->flatpickrOptions[] = $name;
	}

	public function render(): string {
		$group = $this->renderingContext->getViewHelperVariableContainer()->get(GroupViewHelper::class, 'lpcFormGroup');
		if ($group !== null) {
			if(preg_match_all('/\\[([^\\]]*)\\]/',$this->getName(),$matches) > 0) {
				$group->setPropertyPath(implode('.',$matches[1]));
			}
		}

		$this->registerFieldNameForFormTokenGeneration($this->getName());
		$this->pageRenderer->addJsLibrary('flatpickr', 'EXT:lpc_base/Resources/Public/Datepicker/datepicker.js');
		$this->pageRenderer->addCssLibrary('EXT:lpc_base/Resources/Public/Datepicker/datepicker.css');

		$options = [];
		foreach ($this->flatpickrOptions as $option) {
			if (isset($this->arguments[$option])) {
				$options[$option] = $this->arguments[$option];
			}
		}

		if(!isset($options['dateFormat'])) {
			$options['dateFormat'] = empty($options['enableTime']) ? 'd.m.Y' : 'd.m.Y H:i';
		}

		if(!isset($options['locale']) && $this->renderingContext instanceof RenderingContext) {
			$lang = $this->renderingContext->getRequest()?->getAttribute('language');
			if($lang !== null) {
				$options['locale'] = $lang->getTwoLetterIsoCode();
			}
		}

		$this->tag->addAttribute('data-options',json_encode($options,JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR));
		if($this->tag->hasAttribute('class')) {
			$class = $this->tag->getAttribute('class');
			$this->tag->removeAttribute('class');
			$this->tag->addAttribute('class',$class.' lpcDatepicker');
		} else {
			$this->tag->addAttribute('class','lpcDatepicker');
		}
		$this->tag->addAttribute('name',$this->getName().'[date]');
		if(!empty($this->arguments['required'])) {
			$this->tag->addAttribute('required','required');
		}
		if(!empty($this->arguments['readonly'])) {
			$this->tag->addAttribute('readonly','readonly');
		}
		if(!$this->tag->hasAttribute('autocomplete')) {
			$this->tag->addAttribute('autocomplete','off');
		}
		$this->setRespectSubmittedDataValue(true);
		$value = $this->getValueAttribute();
		if(is_array($value)) {
			$value = $value['date'] ?? null;
		} else if($value instanceof \DateTime || $value instanceof Date) {
			$value = $value->format($options['dateFormat']);
		}
		if($value) {
			$this->tag->addAttribute('value',$value);
		}
		return $this->tag->render().'<input type="hidden" name="'.$this->getName().'[dateFormat]" value="'.$options['dateFormat'].'" />';
	}
}
