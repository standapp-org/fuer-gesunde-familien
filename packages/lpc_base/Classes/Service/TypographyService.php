<?php
namespace LPC\LpcBase\Service;

require_once(__DIR__.'/../../Resources/Private/Library/php-typography-master/PhpTypography.php');
require_once(__DIR__.'/../../Resources/Private/Library/php-typography-master/ParseHTML.php');
require_once(__DIR__.'/../../Resources/Private/Library/php-typography-master/ParseText.php');

/**
 * @deprecated use lpc_typography instead
 */
class TypographyService extends \Debach\PhpTypography\PhpTypography
{
	public function __construct()
	{
		parent::__construct(false);

		$this->set_tags_to_ignore();
		$this->set_classes_to_ignore();
		$this->set_ids_to_ignore();

		//hyphenation
		$this->set_hyphenation();
		$this->set_hyphenation_language(\LPC\LpcBase\Utility\LanguageUtility::getCurrentLanguage());
		$this->set_min_length_hyphenation();
		$this->set_min_before_hyphenation();
		$this->set_min_after_hyphenation();
		$this->set_hyphenate_headings();
		$this->set_hyphenate_all_caps();
		$this->set_hyphenate_title_case(); // added in version 1.5
		$this->set_hyphenation_exceptions();
	}

	public function setSmartCharacters($set = true) {
		//smart characters
		$this->set_smart_quotes($set);
		$this->set_smart_quotes_primary($set); /* added in version 1.15 */
		$this->set_smart_quotes_secondary($set); /* added in version 1.15 */
		$this->set_smart_dashes($set);
		$this->set_smart_ellipses($set);
		$this->set_smart_diacritics($set);
		$this->set_diacritic_language($set);
		$this->set_diacritic_custom_replacements($set);
		$this->set_smart_marks($set);
		$this->set_smart_ordinal_suffix($set);
		$this->set_smart_math($set);
		$this->set_smart_fractions($set);
		$this->set_smart_exponents($set);
	}

	public function setSmartSpacing($set = true) {
		//smart spacing
		$this->set_single_character_word_spacing($set);
		$this->set_fraction_spacing($set);
		$this->set_unit_spacing($set);
		$this->set_units($set);
		$this->set_dash_spacing($set);
		$this->set_dewidow($set);
		$this->set_max_dewidow_length($set);
		$this->set_max_dewidow_pull($set);
		$this->set_wrap_hard_hyphens($set);
		$this->set_url_wrap($set);
		$this->set_email_wrap($set);
		$this->set_min_after_url_wrap($set);
		$this->set_space_collapse($set);
	}

	public function setCharacterStyling($set = true) {
		//character styling
		$this->set_style_ampersands($set);
		$this->set_style_caps($set);
		$this->set_style_initial_quotes($set);
		$this->set_style_numbers($set);
		$this->set_initial_quote_tags($set);
	}
}
