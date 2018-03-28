<?php
namespace Lv\Grpc;

use Google\Protobuf\Internal\CodeGeneratorRequest as Request;
use Google\Protobuf\Internal\CodeGeneratorResponse_File as File;
use Google\Protobuf\Internal\FileDescriptorProto as FileDescriptor;
use Google\Protobuf\Internal\ServiceDescriptorProto as ServiceDescriptor;
use Google\Protobuf\Internal\MethodDescriptorProto as MethodDescriptor;
use Google\Protobuf\Internal\SourceCodeInfo_Location;

class SdkGenerator
{
    /**
     * @var Request
     */
    private $request;

    private $indent = 0;

    private $composer_name;

    private $stub_trait;

    private $stub_trait_only;

    /**
     * @var SourceCodeInfo_Location[]
     */
    private $comments;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->parseParameter();
    }

    /**
     * @return File[]
     */
    public function generateFiles() : array
    {
        $all_files = [];

        /** @var FileDescriptor $file */
        foreach ($this->request->getProtoFile() as $file) {
            $this->comments = $this->extractComments($file);
            /** @var ServiceDescriptor $service */
            foreach ($file->getService() as $index => $service) {
                $files = $this->generateFilesForService($file, $service, $index);
                $all_files = array_merge($all_files, $files);
            }
            $this->comments = [];
        }

        if ($this->composer_name) {
            $all_files[] = $this->generateComposer();
        }

        return $all_files;
    }

    public function generateFilesForService(FileDescriptor $file, ServiceDescriptor $service, int $index)
    {
        $php_files = [];

        $php_files[] = $this->generateInterface($file, $service, $index);
        $php_files[] = $this->generateStub($file, $service, $index);
        $php_files[] = $this->generateService($file, $service, $index);

        return $php_files;
    }

    private function generateInterface(FileDescriptor $file, ServiceDescriptor $service, int $service_index)
    {
        $proto_path = $file->getName();
        $namespace = $this->packageToNamespace($file->getPackage());
        $service_name = $this->getServiceName($service);

        $p = [$this, 'e']; $in = [$this, 'in']; $out = [$this, 'out'];

        ob_start();
        $this->generateHeader($proto_path, $namespace);

        $this->generateComment("6,$service_index");
        $p("interface $service_name extends \\Lv\\Grpc\\Service");
        $p("{");
        $in();
        /** @var MethodDescriptor $method */
        foreach ($service->getMethod() as $method_index => $method) {
            $method_name = $method->getName();
            $input_type = $this->packageToNamespace($method->getInputType());
            $output_type = $this->packageToNamespace($method->getOutputType());

            $this->generateComment("6,$service_index,2,$method_index");
            $p("function $method_name($input_type \$request) : $output_type;");
        }
        $out();
        $p("}");
        $content = ob_get_clean();

        $path = $this->getFilePath($namespace, $service_name);
        $file = new File;
        $file->setName($path);
        $file->setContent($content);

        return $file;
    }

    private function generateService(FileDescriptor $file, ServiceDescriptor $service, $service_index)
    {
        $proto_path = $file->getName();
        $package = $file->getPackage();
        $namespace = $this->packageToNamespace($file->getPackage());
        $service_name = $this->getServiceName($service);

        $p = [$this, 'e']; $in = [$this, 'in']; $out = [$this, 'out'];

        ob_start();
        $this->generateHeader($proto_path, $namespace);

        $this->generateComment("6,$service_index");
        $p("trait {$service_name}Trait");
        $p("{");
        $in();
        $p("final public function getMethods()");
        $p("{");
        $in();
        $p("return [");
        $in();
        /** @var MethodDescriptor $method */
        foreach ($service->getMethod() as $method_index => $method) {
            $method_name = $method->getName();
            $p("\"/$package." . $service->getName() . "/$method_name\" => \"do$method_name\",");
        }
        $out();
        $p("];");
        $out();
        $p("}");
        $p();
        $p("final public function getLastErrno()");
        $p("{");
        $p("    throw new \\RuntimeException(__METHOD__.' can only called in client');");
        $p("}");
        $p();
        $p("final public function getLastError()");
        $p("{");
        $p("    throw new \\RuntimeException(__METHOD__.' can only called in client');");
        $p("}");
        $p();
        /** @var MethodDescriptor $method */
        foreach ($service->getMethod() as $method) {
            $method_name = $method->getName();
            $input_type = $this->packageToNamespace($method->getInputType());
            $output_type = $this->packageToNamespace($method->getOutputType());

            $this->generateComment("6,$service_index,2,$method_index");
            $p("final public function do$method_name(\\Lv\\Grpc\\Session \$session, \$data)");
            $p("{");
            $in();
            $p("\$request = new $input_type;");
            $p();
            $p("if (\$session->getMetadata('content-type') === 'application/grpc+proto') {");
            $p("    \$request->mergeFromString(\$data);");
            $p("} else {");
            $p("    \$request->mergeFromJsonString(\$data);");
            $p("}");
            $p();
            $p("\$request->context(\$session);");
            $p();
            $p("return \$this->$method_name(\$request);");
            $out();
            $p("}");
        }
        $out();
        $p("}");
        $content = ob_get_clean();

        $path = $this->getFilePath($namespace, $service_name, "Trait.php");
        $file = new File;
        $file->setName($path);
        $file->setContent($content);

        return $file;
    }

    private function generateStub(FileDescriptor $file, ServiceDescriptor $service, $service_index)
    {
        $proto_path = $file->getName();
        $namespace = $this->packageToNamespace($file->getPackage());
        $service_name = $this->getServiceName($service);
        $package = $file->getPackage();

        $p = [$this, 'e']; $in = [$this, 'in']; $out = [$this, 'out'];

        ob_start();
        $this->generateHeader($proto_path, $namespace);

        $this->generateComment("6,$service_index");
        $p("final class {$service_name}Stub implements $service_name");
        $p("{");
        $in();

        if ($this->stub_trait_only) {
            $p("use {$this->stub_trait_only};");
        } else {
            $p("use \\Lv\\Grpc\\CurlStubTrait;");
            if ($this->stub_trait) {
                $p("use {$this->stub_trait};");
            }
        }

        $p();
        /** @var MethodDescriptor $method */
        foreach ($service->getMethod() as $method_index => $method) {
            $method_name = $method->getName();
            $input_type = $this->packageToNamespace($method->getInputType());
            $output_type = $this->packageToNamespace($method->getOutputType());

            $this->generateComment("6,$service_index,2,$method_index");
            $p("public function $method_name($input_type \$request) : $output_type");
            $p("{");
            $p("    \$reply = new $output_type();");
            $p("");
            $p("    \$this->send(\"/$package." . $service->getName() . "/$method_name\", \$request, \$reply);");
            $p("");
            $p("    return \$reply;");
            $p("}");
        }
        $out();
        $p("}");
        $content = ob_get_clean();

        $path = $this->getFilePath($namespace, $service_name, "Stub.php");
        $file = new File;
        $file->setName($path);
        $file->setContent($content);

        return $file;
    }

    private function generateComposer(): File
    {
        $content = <<<EOT
{
  "name": "$this->composer_name",
  "require": {
    "lvht/grpc": "^1.0"
  },
  "autoload": {
    "psr-0": {
      "": "."
    }
  }
}
EOT;
        $file = new File;
        $file->setName("composer.json");
        $file->setContent($content);
        return $file;
    }

    private function generateHeader($proto_path, $namespace)
    {
        $p = [$this, 'e']; $in = [$this, 'in']; $out = [$this, 'out'];

        $p("<?php");
        $p("// Generated by the protocol buffer compiler.  DO NOT EDIT!");
        $p("// source: $proto_path");
        $p();
        $p("namespace $namespace;");
        $p();
    }

    private function generateComment($path)
    {
        $p = [$this, 'e']; $in = [$this, 'in']; $out = [$this, 'out'];

        $comment = $this->comments[$path] ?? null;
        if ($comment) {
            $p("/**");
            $comment_lines = explode("\n", trim($comment->getLeadingComments()));
            foreach($comment_lines as $line) {
                $p(" * $line");
            }
            $p(" */");
        }
    }

    private function parseParameter()
    {
        $parameter_str = $this->request->getParameter();
        foreach (explode(',', $parameter_str) as $p) {
            $parts = explode('=', $p);
            if (count($parts) == 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);

                $this->$name = $value;
            }
        }
    }

    private function getFilePath($namespace, $service_name, $ext = ".php")
    {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $namespace);

        return $path.DIRECTORY_SEPARATOR.$service_name.$ext;
    }

    /**
     * @return SourceCodeInfo_Location[]
     */
    private function extractComments(FileDescriptor $file) : array
    {
        $comments = [];

        $codeinfo = $file->getSourceCodeInfo();
        $locations = $codeinfo->getLocation();

        foreach ($locations as $location) {
            if (!$location->hasLeadingComments()) {
                continue;
            }
            $paths = iterator_to_array($location->getPath());
            $comments[implode(',', $paths)] = $location;
        }

        return $comments;
    }

    private function getServiceName(ServiceDescriptor $service)
    {
        return $service->getName()."Service";
    }

    private function packageToNamespace($package)
    {
        return str_replace('.', "\\", ucwords($package, '.'));
    }

    private function in()
    {
        $this->indent += 4;
    }

    private function out()
    {
        $this->indent -= 4;
    }

    private function e($line = '')
    {
        if ($line) {
            echo str_pad('', $this->indent, ' '), $line, "\n";
        } else {
            echo "\n";
        }
    }
}
