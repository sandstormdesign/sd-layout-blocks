# SD Layout Blocks
Sandstorm Design Layout Builder Blocks

## Installation
Add to a project as a git submodule:

`git submodule add git@github.com:sandstormdesign/sd-layout-blocks.git ./data/main/web/modules/_custom/sd_layout_blocks`

Update git submodules

`git submodule init && git submodule update --recursive`

## Settings
Visit /admin/config/sd_layout_blocks/settings

### Theme Styles

A list of available classes for any layout block.

One per line, in the following format:

```
class-name-1 | Class Name 1
class-name-2 | Class Name 2
class-name-3 | Class Name 3
```

## Block Styles:

### Heading Level
Provides a variable to determine if a block's title should be H2, H3, etc.

### Heading Alignment
Add a class to denote if the block's title should be left/center/right aligned.

### Theme Styles
Select any available Theme Style classes.

## Blocks

### Content Block
Provides a block to render a node, in any available view mode.


### Large Teaser Block
Custom content block

### WYSIWYG Block
Custom text content, or pick up the current node's body.

### Media Block
  TO DO
