<?php

use LPC\LpcPetition\Hook\PetitionFormPreviewRenderer;

$GLOBALS['TCA']['tt_content']['types']['list']['previewRenderer']['lpcpetition_form'] = PetitionFormPreviewRenderer::class;
