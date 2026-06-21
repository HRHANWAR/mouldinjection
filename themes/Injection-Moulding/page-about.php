<?php
/**
 * Template Name: About
 * Template Post Type: page
 */
get_header();
?>

<main class="im-page-main">
	<?php
// Featured image URL uthane ke liye
$hero_bg = get_the_post_thumbnail_url(get_the_ID(), 'full');
// Agar image nahi hai toh default image lagane ke liye
if (!$hero_bg) {
    $hero_bg = get_template_directory_uri() . '/assets/images/Services/Injection-Moulding.png';
}
?>

<section class="relative min-h-[265px] w-full flex flex-col items-center bg-center bg-cover"
    style="background-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/Services/Injection Moulding.png'); ">

  
</section>

 
<!-- ========= ABOUT US INTRO SECTION ========= -->
<section class="reveal py-16 bg-[#F5F6FA]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <!-- About Us badge -->
    <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#D7FF80]/50 rounded-full mb-6">
      <span class="w-2 h-2 rounded-full bg-[#19191A]"></span>
      <span class="text-[15px] font-medium font-[Poppins] text-[#19191A]">About Us</span>
    </div>
 
    <!-- Heading -->
    <h2 class="text-[36px] lg:text-[40px] font-bold font-[Helvetica] text-[#19191A] leading-tight mb-6 whitespace-nowrap">
      Connecting Tool Owners With Trusted Manufacturers
    </h2>
 
    <!-- Description -->
    <p class="text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed mb-8 max-w-7xl">
      We created this platform to solve a common challenge within the plastics manufacturing industry — many businesses own mould tools but struggle to find reliable, cost-effective production partners with the right machine capacity, technical expertise, and availability.
    </p>
 
    <!-- Goal box — full width, lime green bg, icon + bold text -->
    <div class="bg-[#D7FF80]/50 rounded-2xl px-6 py-5 flex items-center gap-4 max-w-7xl">
      <div class="flex-shrink-0 w-9 h-9 bg-white rounded-full flex items-center justify-center">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/goal.png" class="w-[24px] h-[24px] " alt="Icon">
      </div>
      <p class="text-[#19191A] text-[16px] lg:text-[18px] font-bold font-[Montserrat] leading-relaxed">
        Our goal is simple: to connect tool owners with trusted injection moulding manufacturers — quickly, professionally, and efficiently.
      </p>
    </div>
 
  </div>
</section> 
<!-- ========= WHY WE STARTED SECTION ========= -->
<section class="reveal py-20 bg-[#00191C]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <!-- Header -->
    <div class="text-center mb-14">
      <p class="text-[#C8FF00] font-medium text-[18px] font-[Helvetica] mb-3">What We Do</p>
      <h2 class="font-bold text-[38px] lg:text-[44px] font-[Helvetica] text-[#FFFFFF]">
        Bringing The Industry <span class="text-[#C8FF00]">Together</span>
      </h2>
    </div>
 
    <!-- 3 Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
 
      <!-- Card 1: Tool Owners -->
      <div class="bg-[#0d2d30] border border-white/10 rounded-2xl p-8">
        <div class="w-12 h-12 bg-[#1a3d40] rounded-xl flex items-center justify-center mb-8">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/tool owner.png" class="w-[28px] h-[28px] " alt="Icon">
        </div>
        <h3 class="text-[#EBEDF0] font-bold text-[20px] font-[Helvetica] mb-3">Tool Owners</h3>
        <p class="text-[#8A9BB0] text-[15px] font-[Montserrat] leading-relaxed">
          Businesses who already own mould tools and require production support.
        </p>
      </div>
 
      <!-- Card 2: Machine Owners -->
      <div class="bg-[#0d2d30] border border-white/10 rounded-2xl p-8">
        <div class="w-12 h-12 bg-[#1a3d40] rounded-xl flex items-center justify-center mb-8">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/machineowner.png" class="w-[28px] h-[28px] " alt="Icon">
        </div>
        <h3 class="text-[#EBEDF0] font-bold text-[20px] font-[Helvetica] mb-3">Machine Owners /<br>Manufacturers</h3>
        <p class="text-[#8A9BB0] text-[15px] font-[Montserrat] leading-relaxed">
          Injection moulding companies with available machine capacity looking for new projects.
        </p>
      </div>
 
      <!-- Card 3: Product Businesses -->
      <div class="bg-[#0d2d30] border border-white/10 rounded-2xl p-8">
        <div class="w-12 h-12 bg-[#1a3d40] rounded-xl flex items-center justify-center mb-8">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/productbusiness.png" class="w-[28px] h-[28px] " alt="Icon">
        </div>
        <h3 class="text-[#EBEDF0] font-bold text-[20px] font-[Helvetica] mb-3">Product Businesses</h3>
        <p class="text-[#8A9BB0] text-[15px] font-[Montserrat] leading-relaxed">
          Companies seeking expert support for sourcing moulding production.
        </p>
      </div>
 
    </div>
  </div>
</section>
<!-- ========= WHY WE STARTED SECTION ========= -->
<section class="bg-[#142F32] text-white py-16 px-4 sm:px-6 lg:px-12">
  <div class="reveal max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
 
    <!-- LEFT CONTENT -->
    <div>
      <!-- Tag box -->
      <div class="reveal flex">
        <div class="relative group">
          <div class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]"></div>
          <div class="flex items-center gap-3 px-6 py-2 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
            <span class="w-2 h-2 bg-white rounded-full"></span>
            <span class="text-white text-[12px] lg:text-[18px] font-[Poppins] font-light tracking-wide">Why We Started</span>
          </div>
        </div>
      </div>
 
      <!-- Heading -->
      <h2 class="reveal font-[Helvetica] text-[24px] lg:text-[40px] text-[#FFFFFF] font-medium py-2 leading-tight tracking-widest">
        Solving Real
        <span class="text-[#C8FF00]">Industry Problems</span>
      </h2>
 
      <!-- Description -->
      <p class="reveal mt-2 sm:text-base max-w-2xl text-[#FFFFFF] font-regular text-[18px] font-[Montserrat] leading-relaxed py-2">
        The injection moulding industry is highly skilled, but finding the right partner can often be slow, unclear, and time-consuming. Many businesses face issues such as:
      </p>
    </div>
 
    <!-- RIGHT CONTENT: Problems List -->
    <div class="max-w-4xl mx-auto w-full px-0">
      <div class="relative group mb-2">
        <div class="absolute -inset-1 bg-[#062f2f] bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] to-transparent rounded-[24px] blur opacity-50 group-hover:opacity-100 transition duration-1000"></div>
 
        <div class="relative bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] backdrop-blur-xl border border-white/5 p-4 rounded-[24px] space-y-2">
          <?php
          $problems = [
            '01' => 'Current supplier pricing too high',
            '02' => 'Machine capacity unavailable',
            '03' => 'Production delays',
            '04' => 'Tool transfer requirements',
            '05' => 'Poor communication',
            '06' => 'Difficulty finding UK-based manufacturers',
            '07' => 'Lack of visibility of available machines',
          ];
          foreach ($problems as $num => $problem) : ?>
          <div class="reveal flex items-center gap-4 bg-white/5 rounded-xl px-5 py-3.5">
            <span class="flex-shrink-0 w-8 h-8 rounded-full border border-white/20 flex items-center justify-center text-[#8A9BB0] text-[11px] font-semibold font-[Poppins] tracking-wide"><?php echo $num; ?></span>
            <span class="text-[#EBEDF0] text-[15px] font-medium font-[Montserrat]"><?php echo $problem; ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
 
  </div>
</section>

<!-- ========= HOW WE HELP + OUR DIFFERENCE SECTION ========= -->
<section class="reveal py-20 bg-[#00191C]">
  <div class="max-w-7xl mx-auto px-6">
 
    <!-- Section Header -->
    <div class="reveal text-center mb-12">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-wide font-[Helvetica] mb-3">How We Help</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF] mb-4">
        A Platform That <span class="text-[#C8FF00]">Works For You</span>
      </h2>
      <p class="text-[#FFFFFF] max-w-2xl mx-auto text-[16px] font-[Montserrat] leading-relaxed">
        Where suitable, we facilitate introductions between both parties. All enquiries may be subject to review to maintain platform quality.
      </p>
    </div>
 
    <!-- Row 1: 3 items -->
    <div class="reveal grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
      <?php $row1 = ['Tool details', 'List mould tools requiring production', 'Find spare machine capacity'];
      foreach ($row1 as $item) : ?>
      <div class="flex items-center gap-3 bg-[#0d2d30] border border-white/10 rounded-xl px-6 py-5" style="border-left: 3px solid rgba(200,255,0,0.4);">
        <svg class="w-5 h-5 text-[#C8FF00] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[#EBEDF0] text-[16px] font-medium font-[Montserrat]"><?php echo $item; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Row 2: 2 items -->
    <div class="reveal grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
      <?php $row2 = ['Request manufacturing support', 'Receive matched opportunities'];
      foreach ($row2 as $item) : ?>
      <div class="flex items-center gap-3 bg-[#0d2d30] border border-white/10 rounded-xl px-6 py-5" style="border-left: 3px solid rgba(200,255,0,0.4);">
        <svg class="w-5 h-5 text-[#C8FF00] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[#EBEDF0] text-[16px] font-medium font-[Montserrat]"><?php echo $item; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Row 3: 2 items -->
    <div class="reveal grid grid-cols-1 sm:grid-cols-2 gap-4 mb-12">
      <?php $row3 = ['Reduce sourcing time', 'Connect with trusted suppliers'];
      foreach ($row3 as $item) : ?>
      <div class="flex items-center gap-3 bg-[#0d2d30] border border-white/10 rounded-xl px-6 py-5" style="border-left: 3px solid rgba(200,255,0,0.4);">
        <svg class="w-5 h-5 text-[#C8FF00] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[#EBEDF0] text-[16px] font-medium font-[Montserrat]"><?php echo $item; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Our Difference Card -->
    <div class="reveal bg-[#0d2d30] border border-white/10 rounded-2xl p-8">
      <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/30 rounded-lg flex items-center justify-center mb-5">
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/tool owner.png" class="w-[28px] h-[28px] " alt="Icon">
      </div>
      <h3 class="text-[#EBEDF0] text-[20px] font-semibold font-[Helvetica] mb-4">Our Difference</h3>
      <p class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat] leading-relaxed mb-8">
        Unlike open directories, we take a more managed approach. We help review enquiries, assist with introductions, and aim to connect businesses with the most suitable partners based on:
      </p>
      <!-- Row 1: 4 equal cols -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-y-4 mb-4">
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Trays</span></div>
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Material capability</span></div>
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Tool requirements</span></div>
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Production urgency</span></div>
      </div>
      <!-- Row 2: 3 cols aligned to same 4-col grid (first 3 columns only) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-y-4">
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Volume demand</span></div>
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Certifications</span></div>
        <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span><span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]">Location</span></div>
        <div></div>
      </div>
    </div>
 
  </div>
</section>
<!-- ========= INDUSTRIES WE SUPPORT + OUR MISSION ========= -->
<section class="reveal py-20 bg-[#00191C]">
  <div class="max-w-7xl mx-auto px-6">
 
    <!-- Industries Header -->
    <div class="reveal text-center mb-12">
      <p class="text-[#C8FF00] font-medium text-[20px] tracking-wide font-[Helvetica] mb-4">Industries We Support</p>
      <h2 class="font-medium text-[40px] font-[Helvetica] text-[#FFFFFF]">
        Working Across <span class="text-[#C8FF00]">Many Sectors</span>
      </h2>
    </div>
 
    <!-- Industries Grid — Row 1: 4 cols -->
    <div class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
      <?php
      $industries_row1 = [
    [
        'name' => 'Automotive',
        'image' => get_template_directory_uri() . '/assets/images/Automotive.png'
    ],
    [
        'name' => 'Packaging',
        'image' => get_template_directory_uri() . '/assets/images/Packaging.png'
    ],
    [
        'name' => 'Medical',
        'image' => get_template_directory_uri() . '/assets/images/medical.png'
    ],
    [
        'name' => 'Consumer Products',
        'image' => get_template_directory_uri() . '/assets/images/consumer-products.png'
    ],
];
      foreach ($industries_row1 as $ind) : ?>
      <div class="reveal bg-[#0d2d30] border border-white/10 rounded-2xl p-6 text-left">
        <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center mb-6">
    <img src="<?php echo $ind['image']; ?>"
         alt="<?php echo esc_attr($ind['name']); ?>"
         class="w-7 h-7 object-contain">
</div>
        <h3 class="text-[#EBEDF0] text-[18px] font-medium font-[Helvetica] leading-tight"><?php echo $ind['name']; ?></h3>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Industries Grid — Row 2: 3 cols -->
    <div class="reveal grid grid-cols-1 sm:grid-cols-3 gap-4 mb-12">
      <?php
      $industries_row2 = [
    [
        'name'  => 'Industrial Components',
        'image' => get_template_directory_uri() . '/assets/images/industrial-components.png'
    ],
    [
        'name'  => 'Electronics',
        'image' => get_template_directory_uri() . '/assets/images/electronics.png'
    ],
    [
        'name'  => 'Household Products',
        'image' => get_template_directory_uri() . '/assets/images/household-products.png'
    ],
];
      foreach ($industries_row2 as $ind) : ?>
      <div class="reveal bg-[#0d2d30] border border-white/10 rounded-2xl p-6 text-left">
        <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center mb-6">
        <img
            src="<?php echo esc_url($ind['image']); ?>"
            alt="<?php echo esc_attr($ind['name']); ?>"
            class="w-7 h-7 object-contain"
        >
    </div>
        <h3 class="text-[#EBEDF0] text-[18px] font-medium font-[Helvetica] leading-tight"><?php echo $ind['name']; ?></h3>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Our Mission + Why Work With Us — 2 col cards -->
    <div class="reveal grid grid-cols-1 lg:grid-cols-2 gap-4">
 
      <!-- Our Mission Card -->
      <div class="reveal bg-[#0d2d30] border border-white/10 rounded-2xl p-8">
        <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center mb-6">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/tool owner.png" class="w-[28px] h-[28px] " alt="Icon">
        </div>
        <h3 class="text-[#C8FF00] text-[18px] font-semibold font-[Helvetica] mb-4">Our Mission</h3>
        <p class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat] leading-relaxed">
          To become the UK's leading platform for connecting tool owners with reliable injection moulding manufacturers.
        </p>
      </div>
 
      <!-- Why Work With Us Card -->
      <div class="reveal bg-[#0d2d30] border border-white/10 rounded-2xl p-8">
        <h3 class="text-[#EBEDF0] text-[18px] font-semibold font-[Helvetica] mb-6">Why Work With Us</h3>
        <ul class="space-y-3">
          <?php
          $whyus = [
            'Industry focused platform',
            'Fast supplier matching',
            'UK and international manufacturing network',
            'New business opportunities for moulders',
            'Better visibility for idle machine capacity',
            'Simplified sourcing for tool owners',
          ];
          foreach ($whyus as $item) : ?>
          <li class="flex items-center gap-3">
            <span class="w-2 h-2 rounded-full bg-[#C8FF00] flex-shrink-0"></span>
            <span class="text-[#C8CCD9] text-[16px] font-medium font-[Montserrat]"><?php echo $item; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
 
    </div>
 
  </div>
</section>            
  <!-- ================= Join Us Now section end ================= -->

  <section class="w-full bg-[#142F32] px-6 py-20 lg:px-20 lg:py-20">
    <div
      class="reveal max-w-7xl mx-auto rounded-[24px] bg-[#153F45] text-white p-10 flex flex-col items-center justify-center text-center shadow-2xl shadow-[#153F45]/10">

      <div class="relative group">
        <div
          class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]">
        </div>

        <div class="flex items-center gap-3 px-6 py-2  border border-white/10 rounded-[10px] backdrop-blur-sm">
          <span class="w-2 h-2 bg-white rounded-full"></span>

          <span class="text-white font-regular text-[18px] font-[Poppins] font-light tracking-wide">
            Step 05
          </span>
        </div>
      </div>

      <h2
        class="text-[32px] md:text-[40px] lg:text-[40px] font-bold font-[Poppins] tracking-wide leading-tight  mt-3">
        Get Started
        <span class="text-[#C8FF00]">Today</span>
      </h2>

      <p class="text-[16px] md:text-[16px] text-slate-200 font-[Poppins] font-medium max-w-xl mb-6 mt-3">
       Whether you own mould tools or have machines available, we'd love to hear from you.
      </p>

      <!-- Buttons -->
            <div class=" flex flex-col sm:flex-row items-center justify-center gap-5">

                <a href="<?php echo esc_url(home_url('/register')); ?>"
                   class="inline-flex items-center gap-2 bg-lime-400 hover:bg-lime-300 text-black font-medium px-8 py-2 rounded-full transition-all duration-300">
                    Register Your Business
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M7 17L17 7M17 7H8M17 7V16" />
                    </svg>
                </a>

                <a href="/contact-us"
                   class="inline-flex items-center gap-2 border border-white text-white hover:bg-white hover:text-black px-8 py-2 rounded-full transition-all duration-300">
                    Contact Us
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-5 h-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M7 17L17 7M17 7H8M17 7V16" />
                    </svg>
                </a>

            </div>

    </div>
  </section>
  <!-- ================= Join Us Now section end ================= -->

</main>
<?php get_footer(); ?>



