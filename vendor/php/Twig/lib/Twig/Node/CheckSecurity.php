<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Twig\Compiler;
use Twig\Node\Node;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Twig_Node_CheckSecurity extends Node
{
    private $usedFilters;
    private $usedTags;
    private $usedFunctions;

    public function __construct(array $usedFilters, array $usedTags, array $usedFunctions)
    {
        $this->usedFilters = $usedFilters;
        $this->usedTags = $usedTags;
        $this->usedFunctions = $usedFunctions;

        parent::__construct();
    }

    public function compile(Compiler $compiler)
    {
        $tags = $filters = $functions = [];
        foreach (['tags', 'filters', 'functions'] as $type) {
            foreach ($this->{'used'.ucfirst($type)} as $name => $node) {
                if ($node instanceof Node) {
                    ${$type}[$name] = $node->getTemplateLine();
                } else {
                    ${$type}[$node] = null;
                }
            }
        }

        $compiler
            ->write('$tags = ')->repr(array_filter($tags))->raw(";\n")
            ->write('$filters = ')->repr(array_filter($filters))->raw(";\n")
            ->write('$functions = ')->repr(array_filter($functions))->raw(";\n\n")
            ->write("try {\n")
            ->indent()
            ->write("\$this->extensions['Twig_Extension_Sandbox']->checkSecurity(\n")
            ->indent()
            ->write(!$tags ? "[],\n" : "['".implode("', '", array_keys($tags))."'],\n")
            ->write(!$filters ? "[],\n" : "['".implode("', '", array_keys($filters))."'],\n")
            ->write(!$functions ? "[]\n" : "['".implode("', '", array_keys($functions))."']\n")
            ->outdent()
            ->write(");\n")
            ->outdent()
            ->write("} catch (SecurityError \$e) {\n")
            ->indent()
            ->write("\$e->setSourceContext(\$this->source);\n\n")
            ->write("if (\$e instanceof SecurityNotAllowedTagError && isset(\$tags[\$e->getTagName()])) {\n")
            ->indent()
            ->write("\$e->setTemplateLine(\$tags[\$e->getTagName()]);\n")
            ->outdent()
            ->write("} elseif (\$e instanceof SecurityNotAllowedFilterError && isset(\$filters[\$e->getFilterName()])) {\n")
            ->indent()
            ->write("\$e->setTemplateLine(\$filters[\$e->getFilterName()]);\n")
            ->outdent()
            ->write("} elseif (\$e instanceof SecurityNotAllowedFunctionError && isset(\$functions[\$e->getFunctionName()])) {\n")
            ->indent()
            ->write("\$e->setTemplateLine(\$functions[\$e->getFunctionName()]);\n")
            ->outdent()
            ->write("}\n\n")
            ->write("throw \$e;\n")
            ->outdent()
            ->write("}\n\n")
        ;
    }
}

class_alias('Twig_Node_CheckSecurity', 'Twig\Node\CheckSecurityNode', false);
