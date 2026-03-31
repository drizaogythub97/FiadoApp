package net.fiadoapp.app;

import android.annotation.SuppressLint;
import android.app.AlertDialog;
import android.content.Context;
import android.content.Intent;
import android.graphics.Bitmap;
import android.net.ConnectivityManager;
import android.net.NetworkInfo;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.util.Base64;
import android.view.View;
import android.webkit.CookieManager;
import android.webkit.DownloadListener;
import android.webkit.JavascriptInterface;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.FileProvider;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import java.io.File;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;

public class MainActivity extends AppCompatActivity {

    private static final String APP_URL   = "https://fiadoapp.net";
    private static final String AUTHORITY = "net.fiadoapp.app.provider";

    private WebView            webView;
    private ProgressBar        progressBar;
    private LinearLayout       offlineView;
    private SwipeRefreshLayout swipeRefresh;

    @SuppressLint("SetJavaScriptEnabled")
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        webView      = findViewById(R.id.webView);
        progressBar  = findViewById(R.id.progressBar);
        offlineView  = findViewById(R.id.offlineView);
        swipeRefresh = findViewById(R.id.swipeRefresh);

        // ── Sessão persistente ──
        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);
        cookieManager.setAcceptThirdPartyCookies(webView, true);

        // Cor do indicador de refresh
        swipeRefresh.setColorSchemeResources(R.color.brand);
        swipeRefresh.setProgressBackgroundColorSchemeResource(R.color.bg_surface_1);
        swipeRefresh.setOnRefreshListener(() -> {
            if (isOnline()) {
                webView.reload();
            } else {
                swipeRefresh.setRefreshing(false);
                showOffline();
            }
        });

        // Configurações do WebView
        WebSettings s = webView.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setCacheMode(WebSettings.LOAD_NO_CACHE);
        s.setAllowFileAccess(false);
        s.setSupportZoom(false);
        s.setBuiltInZoomControls(false);
        s.setDisplayZoomControls(false);
        s.setMediaPlaybackRequiresUserGesture(false);

        webView.setWebViewClient(new WebViewClient() {
            @Override
            public void onPageStarted(WebView view, String url, Bitmap favicon) {
                progressBar.setVisibility(View.VISIBLE);
                offlineView.setVisibility(View.GONE);
                webView.setVisibility(View.VISIBLE);
            }

            @Override
            public void onPageFinished(WebView view, String url) {
                progressBar.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);
                CookieManager.getInstance().flush();
            }

            @Override
            public boolean shouldOverrideUrlLoading(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();

                // Permite navegação interna do app
                if (url.contains("fiadoapp.net")) return false;

                // Abre WhatsApp via Intent externo (wa.me ou deep link whatsapp://)
                // Isso evita que o WebView tente navegar para o wa.me e quebre o fluxo
                if (url.contains("wa.me") || url.startsWith("whatsapp://")) {
                    try {
                        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url));
                        startActivity(intent);
                    } catch (Exception e) {
                        Toast.makeText(MainActivity.this,
                            "WhatsApp não encontrado no dispositivo.", Toast.LENGTH_SHORT).show();
                    }
                    return true; // Interceptado: WebView não navega
                }

                // Bloqueia qualquer outro domínio externo
                return true;
            }

            @Override
            public void onReceivedError(WebView view, int errorCode,
                                        String description, String failingUrl) {
                progressBar.setVisibility(View.GONE);
                swipeRefresh.setRefreshing(false);
                showOffline();
            }
        });

        webView.setWebChromeClient(new WebChromeClient() {
            @Override
            public void onProgressChanged(WebView view, int newProgress) {
                progressBar.setProgress(newProgress);
            }
        });

        // ── Interface nativa para imagens geradas por Canvas (blob URL não é HTTP) ──
        webView.addJavascriptInterface(new FiadoAppInterface(), "FiadoAppNativo");

        // ── Download interceptado → busca o arquivo e abre o compartilhar ──
        webView.setDownloadListener((downloadUrl, userAgent, contentDisposition, mimeType, contentLength) -> {
            String filename = extractFilename(contentDisposition, downloadUrl, mimeType);
            String cookies  = CookieManager.getInstance().getCookie(downloadUrl);

            Toast.makeText(this, "Preparando documento...", Toast.LENGTH_SHORT).show();

            new Thread(() -> fetchAndShare(downloadUrl, filename, mimeType, cookies)).start();
        });

        // Restaurar estado ou carregar URL
        if (savedInstanceState != null) {
            webView.restoreState(savedInstanceState);
        } else {
            if (isOnline()) {
                webView.loadUrl(APP_URL);
            } else {
                showOffline();
            }
        }

        findViewById(R.id.btnRetry).setOnClickListener(v -> {
            if (isOnline()) {
                offlineView.setVisibility(View.GONE);
                webView.setVisibility(View.VISIBLE);
                progressBar.setVisibility(View.VISIBLE);
                webView.loadUrl(APP_URL);
            }
        });
    }

    // ── Interface JavaScript → Java para imagens geradas via Canvas ────────────
    // O JS chama FiadoAppNativo.downloadPng(dataUrl, filename) com o base64 do canvas.
    // Isso contorna a limitação do DownloadListener com blob: URLs.
    public class FiadoAppInterface {

        @JavascriptInterface
        public void downloadPng(String dataUrl, String filename) {
            // dataUrl = "data:image/png;base64,XXXXX..."
            try {
                String base64 = dataUrl.contains(",") ? dataUrl.split(",")[1] : dataUrl;
                byte[] bytes  = Base64.decode(base64, Base64.DEFAULT);

                File dir = new File(getCacheDir(), "downloads");
                if (!dir.exists()) dir.mkdirs();

                // Garante extensão .png
                String fname = (filename != null && !filename.isEmpty()) ? filename : "imagem_fiadoapp.png";
                if (!fname.toLowerCase().endsWith(".png")) fname += ".png";

                File outFile = new File(dir, fname);
                try (FileOutputStream fos = new FileOutputStream(outFile)) {
                    fos.write(bytes);
                }

                Uri fileUri = FileProvider.getUriForFile(MainActivity.this, AUTHORITY, outFile);
                final String finalFname = fname;

                new Handler(Looper.getMainLooper()).post(() -> {
                    Intent share = new Intent(Intent.ACTION_SEND);
                    share.setType("image/png");
                    share.putExtra(Intent.EXTRA_STREAM, fileUri);
                    share.putExtra(Intent.EXTRA_SUBJECT, finalFname);
                    share.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
                    startActivity(Intent.createChooser(share, "Salvar / Compartilhar imagem"));
                });

            } catch (Exception e) {
                showErrorOnUI("Erro ao salvar imagem.");
            }
        }
    }

    // ── Faz o download do arquivo em background e abre o share sheet ──
    private void fetchAndShare(String downloadUrl, String filename, String mimeType, String cookies) {
        try {
            // Garante pasta de cache
            File dir = new File(getCacheDir(), "downloads");
            if (!dir.exists()) dir.mkdirs();

            File outFile = new File(dir, filename);

            // Baixa o arquivo com os cookies da sessão
            URL url = new URL(downloadUrl);
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("GET");
            if (cookies != null && !cookies.isEmpty()) {
                conn.setRequestProperty("Cookie", cookies);
            }
            conn.setConnectTimeout(15_000);
            conn.setReadTimeout(30_000);
            conn.connect();

            if (conn.getResponseCode() != HttpURLConnection.HTTP_OK) {
                showErrorOnUI("Erro ao baixar documento (" + conn.getResponseCode() + ")");
                return;
            }

            // Salva em disco
            try (InputStream in = conn.getInputStream();
                 FileOutputStream out = new FileOutputStream(outFile)) {
                byte[] buffer = new byte[8192];
                int len;
                while ((len = in.read(buffer)) != -1) {
                    out.write(buffer, 0, len);
                }
            }
            conn.disconnect();

            // Obtém URI segura via FileProvider
            Uri fileUri = FileProvider.getUriForFile(this, AUTHORITY, outFile);

            // Abre o compartilhar na UI thread
            new Handler(Looper.getMainLooper()).post(() -> {
                Intent share = new Intent(Intent.ACTION_SEND);
                share.setType(mimeType != null && !mimeType.isEmpty() ? mimeType : "application/pdf");
                share.putExtra(Intent.EXTRA_STREAM, fileUri);
                share.putExtra(Intent.EXTRA_SUBJECT, filename);
                share.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION);
                startActivity(Intent.createChooser(share, "Compartilhar documento"));
            });

        } catch (Exception e) {
            showErrorOnUI("Não foi possível preparar o documento.");
        }
    }

    // ── Extrai o nome do arquivo do header Content-Disposition ou da URL ──
    private String extractFilename(String contentDisposition, String url, String mimeType) {
        // Tenta extrair do Content-Disposition: attachment; filename="arquivo.pdf"
        if (contentDisposition != null && contentDisposition.contains("filename=")) {
            String[] parts = contentDisposition.split("filename=");
            if (parts.length > 1) {
                String name = parts[1].replace("\"", "").trim();
                // Remove parâmetros extras (charset etc.)
                int semi = name.indexOf(';');
                if (semi > 0) name = name.substring(0, semi).trim();
                if (!name.isEmpty()) return name;
            }
        }
        // Fallback: usa o último segmento da URL
        try {
            String path = new URL(url).getPath();
            String last = path.substring(path.lastIndexOf('/') + 1);
            if (!last.isEmpty()) return last;
        } catch (Exception ignored) {}
        // Último fallback
        return "documento_fiadoapp.pdf";
    }

    private void showErrorOnUI(String msg) {
        new Handler(Looper.getMainLooper()).post(() ->
                Toast.makeText(this, msg, Toast.LENGTH_LONG).show());
    }

    // ── Ciclo de vida ──

    @Override
    protected void onPause() {
        super.onPause();
        CookieManager.getInstance().flush();
    }

    @Override
    protected void onStop() {
        super.onStop();
        CookieManager.getInstance().flush();
    }

    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) webView.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onSaveInstanceState(Bundle outState) {
        super.onSaveInstanceState(outState);
        webView.saveState(outState);
    }

    private void showOffline() {
        webView.setVisibility(View.GONE);
        offlineView.setVisibility(View.VISIBLE);
    }

    private boolean isOnline() {
        ConnectivityManager cm =
                (ConnectivityManager) getSystemService(Context.CONNECTIVITY_SERVICE);
        if (cm == null) return false;
        NetworkInfo info = cm.getActiveNetworkInfo();
        return info != null && info.isConnected();
    }
}
