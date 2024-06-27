<?php
namespace LPC\LpcBase\ViewHelpers\Filter;

use TYPO3\CMS\Core\Domain\ConsumableString;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;

class ListViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
	protected $tagName = 'div';

	private int $numItems = 0;
	private ?int $currentItem = null;

	/**
	 * @var array<string, array{values?: array<string, array{items: int[], label?: string}>, ignore?: int[]}>
	 */
	private array $options = [];

	/**
	 * @var array<string, array{control: AbstractControlViewHelper, name: string, id: string}>
	 */
	private array $controls = [];

	/**
	 * @var int[]
	 */
	private array $captions = [];

	private ?string $fulltextSearchInput = null;

	/**
	 * @var string[]
	 */
	private array $onChange = [];

	private ?int $pageSize = null;

	/**
	 * @var array<array{class: string, template: string}>
	 */
	private array $numberDisplays = [];

	public function initializeArguments(): void {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
	}

	public function render() {
		$this->viewHelperVariableContainer->add(self::class, 'filterlist', $this);
		$content = $this->renderChildren();
		$this->viewHelperVariableContainer->remove(self::class, 'filterlist');

		$id = uniqid('lpcFilterList');
		$this->tag->addAttribute('id',$id);

		if(count($this->controls) > 0) {
			$content = strtr($content,
				array_map(
					function($control) {
						$options = [];
						foreach($this->options[$control['name']]['values'] ?? [] as $value => $option) {
							$options[$value] = $option['label'] ?? $value;
						}
						if ($control['control']->hideIfEmpty() && count($options) === 0) return '';
						return $control['control']->renderControl($options, $control['id']);
					},
					$this->controls
				)
			);
		}

		$this->tag->setContent($content);
		$this->tag->addAttribute('class', ltrim($this->tag->getAttribute('class').' lpcFilterList', ' '));
		return $this->tag->render().$this->renderFilterScript($id);
	}

	public function startItem(): void {
		$this->currentItem = $this->numItems++;
	}

	public function endItem(): void {
		$this->currentItem = null;
	}

	public function addCaption(): void {
		if($this->currentItem !== null) {
			throw new \BadMethodCallException('filter.caption viewhelper must not be used inside a filter.item viewhelper');
		}
		$this->captions[] = $this->numItems;
	}

	public function addValue(string $name, string $value, ?string $label): void {
		if($this->currentItem === null) {
			throw new \BadMethodCallException('filter.value viewhelper must be used inside a filter.item viewhelper');
		}
		if(!isset($this->options[$name]['values'][$value])) {
			$this->options[$name]['values'][$value] = ['items' => []];
		}
		$this->options[$name]['values'][$value]['items'][] = $this->currentItem;
		if(empty($this->options[$name]['values'][$value]['label']) && $label !== null) {
			$this->options[$name]['values'][$value]['label'] = $label;
		}
	}

	public function ignoreValue(string $name): void {
		if($this->currentItem === null) {
			throw new \BadMethodCallException('filter.value viewhelper must be used inside a filter.item viewhelper');
		}
		$this->options[$name]['ignore'][] = $this->currentItem;
	}

	public function addFilterControl(AbstractControlViewHelper $control, string $name): string {
		$id = uniqid('lpcFilterControl');
		$placeholder = '###'.$id.'###';
		$this->controls[$placeholder] = ['control' => $control, 'name'=> $name, 'id' => $id];
		return $placeholder;
	}

	public function getFulltextSearchInputId(): string {
		if ($this->fulltextSearchInput !== null) {
			throw new \LogicException('There can be only one fulltext search per filter list.');
		}
		return $this->fulltextSearchInput = uniqid('lpcFilterSearch');
	}

	public function onChange(string $callback): void {
		$this->onChange[] = $callback;
	}

	/**
	 * @param int<1, max> $pageSize
	 */
	public function activatePagination(int $pageSize): void {
		if ($this->pageSize !== null && $this->pageSize !== $pageSize) {
			throw new \LogicException('All pagination viewhelpers inside one list must use the same page size.');
		}
		$this->pageSize = $pageSize;
	}

	public function addNumberDisplay(string $template): string {
		$class = uniqid('numberOfHits');
		$this->numberDisplays[] = ['class' => $class, 'template' => $template];
		return $class;
	}

	private function renderFilterScript(string $id): string {
		if(empty($this->controls) && $this->fulltextSearchInput === null) {
			return '';
		}
		$nonce = '';
		if ($this->renderingContext instanceof RenderingContext) {
			$nonceAttr = $this->renderingContext->getRequest()?->getAttribute('nonce');
			if ($nonceAttr instanceof ConsumableString) {
				$nonce = ' nonce="'.$nonceAttr->consume().'"';
			}
		}
		ob_start();
		try {
			?>
				<script<?= $nonce ?>>
					window.addEventListener('load', function() {
						var list = document.getElementById('<?= $id ?>');
						var items = list.querySelectorAll('.lpcFilterItem');
						if(items.length !== <?= $this->numItems ?>) {
							console.warn(`the list contains ${items.length} elements with class "lpcFilterItem" but <?= $this->numItems ?> were registered by a filter.item viewhelper.`);
						}

						<?php if (count($this->captions) > 0) { ?>
							var captions = list.querySelectorAll('.lpcFilterCaption');
							if(captions.length !== <?= count($this->captions) ?>) {
								console.warn(`the list contains ${captions.length} elements with class "lpcFilterCaption" but <?= count($this->captions) ?> were registered by a filter.caption viewhelper.`);
							}
							var captionIndex = <?= json_encode($this->captions) ?>;
						<?php } ?>

						<?php if (count($this->numberDisplays) > 0) { ?>
							var numberDisplays = <?= json_encode($this->numberDisplays) ?>.map((nd) => ({el: list.getElementsByClassName(nd.class)[0], tmpl: nd.template}));
						<?php } ?>

						var filteredItems = null;

						<?php if(!empty($this->controls)) { ?>
							var selections = [];
							[<?= implode(',',array_map(function($control) {
								$options = $this->options[$control['name']]['values'] ?? [];
								return '{
									id: \''.$control['id'].'\',
									options: '.json_encode(array_combine(array_keys($options),array_column($options, 'items'))).',
									ignore: '.json_encode($this->options[$control['name']]['ignore'] ?? []).',
									init: function(callback) { '.$control['control']->renderScript('callback', $control['id']).' }
								}';
							}, array_values($this->controls))) ?>].forEach(function(control) {
								var selection = {options: null};
								selections.push(selection);
								control.init(function(values) {
									selection.options = values.length === 0
										? null
										: values.reduce(function(options, value) {
											return options.concat(control.options[value]);
										}, control.ignore);
									filteredItems = selections.reduce(function(carry, current) {
										if(current.options === null) {
											return carry;
										} else if(carry === null) {
											return new Set(current.options);
										} else {
											return new Set(current.options.filter(function(item) {
												return carry.has(item);
											}));
										}
									}, null);
									updateItemList();
								});
							});
						<?php } ?>

						<?php if ($this->fulltextSearchInput !== null) { ?>
							var fulltextSearchInput = document.getElementById('<?= $this->fulltextSearchInput ?>');
							fulltextSearchInput.addEventListener('keyup', function() {
								updateItemList();
							});
						<?php } ?>

						<?php if ($this->pageSize !== null) { ?>
							var page = 0;
							var paginations = list.querySelectorAll('.lpcFilterPagination');
							updateItemList();
						<?php } ?>

						function updateItemList() {
							<?php if ($this->pageSize !== null) { ?>
								let visibleCount = 0;
								if (filteredItems && filteredItems.size < page * <?= $this->pageSize ?>) {
									page = Math.ceil(filteredItems.size / <?= $this->pageSize ?>) - 1;
								}
							<?php } ?>
							<?php if (count($this->captions) > 0) { ?>
								var caption = 0;
							<?php } ?>
							<?php if ($this->fulltextSearchInput !== null) { ?>
								var searchWords = fulltextSearchInput.value.split(' ').filter(function(v) {return v.length > 0;});
								var searchPatterns = searchWords.map(function(word) {
									return new RegExp(
										word.replace(/[\\\^\$\.\|\?\+\(\)\[\]]/g, function(match) {
											return '\\'+match;
										}).replace(/\*/g,'\\w*'),
										'i'
									);
								})
							<?php } ?>
							for(var i = 0; i < items.length; i++) {
								var visible = filteredItems === null || filteredItems.has(i);
								<?php if ($this->fulltextSearchInput !== null) { ?>
									if(searchWords.length > 0 && visible) {
										visible = searchPatterns.every(function(pattern) {
											return pattern.test(items[i].textContent);
										});
									}
								<?php } ?>
								<?php if ($this->pageSize !== null) { ?>
									if (visible) {
										if (visibleCount < page * <?= $this->pageSize ?> || visibleCount >= (page + 1) * <?= $this->pageSize ?>) {
											visible = false;
										}
										visibleCount++;
									}
								<?php } ?>
								var display = visible ? null : 'none';
								if(items[i].style.display !== display) {
									items[i].style.display = visible ? null : 'none';
									<?php foreach($this->onChange as $callback) { ?>
										<?= $callback ?>(i, visible);
									<?php } ?>
								}
								<?php if (count($this->captions) > 0) { ?>
									if((caption+1 < captionIndex.length && captionIndex[caption+1] == i)) {
										captions[caption].style.display = 'none';
										caption++;
									}
									if(i+1 == items.length && !visible) {
										while(caption < captionIndex.length) {
											captions[caption].style.display = 'none';
											caption++;
										}
									}
									if(caption < captionIndex.length && i >= captionIndex[caption] && visible) {
										captions[caption].style.display = null;
										caption++;
									}
								<?php } ?>
							}
							<?php if ($this->pageSize !== null) { ?>
								const numPages = Math.ceil(visibleCount / <?= $this->pageSize ?>);
								for (const pagination of paginations) {
									pagination.innerText = '';
									if (numPages > 1) {
										let prev = document.createElement('span');
										prev.classList.add('previous');
										prev.addEventListener('click', () => goToPage(Math.max(0, page - 1)));
										pagination.append(prev);
										pagination.append(...Array.from({length: numPages}, (_, i) => {
											const el = document.createElement('span');
											el.textContent = i+1;
											if (i === page) {
												el.classList.add('current');
											} else {
												el.addEventListener('click', () => goToPage(i));
											}
											return el;
										}));
										let next = document.createElement('span');
										next.classList.add('next');
										next.addEventListener('click', () => goToPage(Math.min(numPages - 1, page + 1)));
										pagination.append(next);
									}
								}
							<?php } ?>
							<?php if (count($this->numberDisplays) > 0) { ?>
								const total = filteredItems ? filteredItems.size : items.length;
								for (const nb of numberDisplays) {
									nb.el.textContent = total === 0 ? '' : nb.tmpl.replaceAll(/\[(\w+)\]/g, (match, name) => {
										switch (name) {
											case 'start':
												return <?php if($this->pageSize === null) { ?> 1 <?php } else { ?> page * <?= $this->pageSize ?> + 1 <?php } ?>;
											case 'end':
												return <?php if($this->pageSize === null) { ?> total <?php } else { ?> Math.min(total, (page + 1) * <?= $this->pageSize ?>) <?php } ?>;
											case 'total':
												return total
											default:
												return match;
										}
									});
								}
							<?php } ?>
						}

						function goToPage(targetPage) {
							if (page !== targetPage) {
								page = targetPage;
								updateItemList();
							}
						}
					});
				</script>
			<?php
		} catch(\Throwable $e) {
			ob_end_clean();
			throw $e;
		}
		return ob_get_clean() ?: '';
	}
}
