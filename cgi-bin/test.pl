use LaTeXRender;
use HTML::Entities;
use CGI qw(escapeHTML);
use Cwd;

# Make some examples
$string = <<'ENDTEX';
[tex]
\displaystyle 2\sum_{i=1}^n a_i \int^b_a f_i(x)g_i(x)\,\mathrm{d}x 
[/tex]
and
[tex]
E = mc^2
[/tex]
or sometimes,
[tex]
\displaystyle s(q) < \int_a^q \|\mathbf{x}^\prime(q)\|\, dq = \int_a^q \sqrt{dx_1^2 + \cdots dx_n^2}, \quad L=s(b)
[/tex]
Finally, most of the time it is safe to assume
[tex]
\displaystyle
\begin{pmatrix}
1 & 0 & 0 & 0 \\
0 & a & 0 & 0 \\
0 & 0 & -1 & 0 
\end{pmatrix}
[/tex]
is not the identity matrix in [tex]R^n[/tex], and that
[tex]
\displaystyle 2 \Lambda_{ij,k}^G = \frac{\delta g_{pr}}{\delta x^q} \frac{\delta x^p}{\delta \xi^i} \frac{\delta x^r}{\delta \xi^k} \frac{\delta x^q}{\delta \xi^j} + g_{pr} \frac{\delta^2x^p}{\delta \xi^j \delta \xi^i} \frac{\delta x^r}{\delta \xi^k} + g_{pr} \frac{\delta^2x^r}{\delta \xi^k \delta \xi^j} \frac{\delta x^p}{\delta \xi^i} \quad \frac{D^2}{d\tau^2} \frac{\delta x^h}{\delta q} = \frac{\delta}{\delta q}\left( -\Lambda_{ik}^h \frac{dx^i}{d\tau} \frac{dx^k}{d\tau}\right) + \frac{\delta\Lambda^{h}{ij}}{\delta x^k} \frac{dx^i}{d\tau}\frac{\partial x^j}{\partial q}\frac{dx^k}{d\tau},
[/tex]
besides being hard to look at, is also too large to pass inspection.
ENDTEX

$formula = "\\sqrt{\\alpha}";
$p = getcwd();
print $p . "\n";
$latex = new LaTeXRender( "$p/pictures", "pictures", "$p/tmp");
$url = $latex->getFormulaURL($formula);

print "url $url e1 " . $_errorcode . " e2 ".  $_errorextra . "\n";

1;

