# Pagodas
Pagodas is a simple, lightweight template engine with inheritance and variables allowing you to write and use clean html code.

## Usage
base.html:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{$title}}</title>
</head>
<body>
    {{header mainHeader.html}}
    {{main example.html}}
</body>
</html>
```
mainHeader.html
```html
<header>
    <nav>
        <a href="{{$url}}">Home</a>
    </nav>
</header>
```
helloWorld.html
```html
{{extends base.main}}
<p>Hello World!</p>
```
php
```php
$pagodas = new Pagodas('path/to/templatesDir', 'path/to/cacheFolder', $psr16CacheInterface);
$pagodas->render('helloWorld.html', ['title' => "Pagodas", 'url' => "http://example.com"]);
```
**Result**:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pagodas</title>
</head>
<body>
    <header>
        <nav>
            <a href="http://example.com">Home</a>
        </nav>
    </header>
    <p>Hello World!</p>
</body>
</html>
```

## Variables
Variables can be included in the html templates with `{{$variableName}}`.
When rendering the template it will be replaced with the content of the templateData array `['variableName' => 'value']`.

## Include Templates
Templates can be included with `{{sectionName default.html}}`. A default template MUST be specified but can
be overwritten by the templates array `['sectionName' => 'childTemplate.html']`.

**ATTENTION:** A template that references itself or
one of its parents will result in an infinite loop!

## Inheritance
Templates can extend other templates to build a chain of templates from bottom-to-top using `{{extends templateA.sectionB}}`.
Here `templateA.sectionB` references the section `{{sectionB default.html}}` in file `template.html` that will be replaced
by the template that extends its parent. 
