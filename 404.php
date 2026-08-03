<?php get_header(); ?>
<div class="min-h-[70vh] flex items-center justify-center bg-background px-4">
    <div class="text-center">
        <p class="text-[8rem] font-extrabold leading-none text-primary/10 md:text-[12rem]">۴۰۴</p>
        <h1 class="mt-2 text-2xl font-extrabold text-foreground md:text-3xl">صفحه‌ای پیدا نشد!</h1>
        <p class="mt-3 text-sm text-muted-foreground">صفحه‌ای که دنبالش می‌گردید حذف شده یا آدرسش تغییر کرده.</p>
        <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
            <a href="<?php echo esc_url( home_url('/') ); ?>"
                class="rounded-xl bg-primary px-6 py-3 text-sm font-bold text-primary-foreground transition-colors hover:bg-primary/90">
                بازگشت به خانه
            </a>
            <a href="<?php echo esc_url( wc_get_page_permalink('shop') ); ?>"
                class="rounded-xl border border-border bg-card px-6 py-3 text-sm font-semibold text-foreground transition-colors hover:bg-secondary">
                مشاهده فروشگاه
            </a>
        </div>
        <form role="search" method="get" action="<?php echo esc_url( home_url('/') ); ?>"
            class="mx-auto mt-10 flex max-w-sm items-center gap-2 rounded-2xl border border-border bg-card p-2 shadow-sm focus-within:ring-2 focus-within:ring-ring">
            <input type="search" name="s" placeholder="جست‌وجو در سایت..."
                class="flex-1 bg-transparent px-3 py-2 text-sm text-foreground outline-none placeholder:text-muted-foreground" />
            <input type="hidden" name="post_type" value="product" />
            <button type="submit" class="shrink-0 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">جست‌وجو</button>
        </form>
    </div>
</div>
<?php get_footer(); ?>
