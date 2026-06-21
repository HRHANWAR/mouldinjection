<?php
/**
 * Template Name: How It Work
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
              <!-- ========= INTRO SECTION ========= -->
<section class="reveal py-16 bg-[#F5F6FA]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <!-- Top row: Left text + Right image -->
    <div class="flex flex-col lg:flex-row gap-12 items-center mb-12">
 
      <!-- Left -->
      <div class="w-full lg:w-1/2">
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#D7FF80] rounded-full mb-6">
          <span class="w-2 h-2 rounded-full bg-[#19191A]"></span>
          <span class="text-[15px] font-medium font-[Poppins] text-[#19191A]">How It Works</span>
        </div>
        <h1 class="text-[32px] lg:text-[40px] font-bold font-[Helvetica] text-[#19191A] leading-tight mb-6">
          Connecting Tool Owners With Reliable Manufacturers
        </h1>
        <p class="text-[#767C8C] text-[15px] font-[Montserrat] leading-relaxed">
          Our platform makes it simple for businesses with mould tools to find trusted injection moulding manufacturers with the right machine capacity, experience, and availability. Whether you need urgent production support or long-term manufacturing, we help streamline the process.
        </p>
      </div>
 
      <!-- Right: Network image -->
      <div class="w-full lg:w-1/2">
        <div class="rounded-2xl overflow-hidden shadow-md w-full h-[300px] bg-[#1a6b8a] flex items-center justify-center">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/how-it-work.png"
               alt="Connecting manufacturers network"
               class="w-full h-full object-cover rounded-2xl">
        </div>
      </div>
 
    </div>
 
    <!-- Step cards: Row 1 (3 cols) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
      <?php
      $row1 = [
    [
        'num'   => 'Step 1',
        'label' => 'Register Your Business',
        'image' => get_template_directory_uri() . '/assets/images/step1.png'
    ],
    [
        'num'   => 'Step 2',
        'label' => 'Upload Your Requirements',
        'image' => get_template_directory_uri() . '/assets/images/step2.png'
    ],
    [
        'num'   => 'Step 3',
        'label' => 'We Review & Match',
        'image' => get_template_directory_uri() . '/assets/images/step3.png'
    ],
];
      foreach ($row1 as $step) : ?>
      <div class="bg-white border border-[#E8EAEF] rounded-2xl px-6 py-6 shadow-sm">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-9 h-9 bg-[#F0F1F5] rounded-full flex items-center justify-center flex-shrink-0">
            <img
        src="<?php echo esc_url($step['image']); ?>"
        alt="<?php echo esc_attr($step['label']); ?>"
        class="w-5 h-5 object-contain"
    >
          </div>
          <span class="text-[#19191A] text-[18px] font-bold font-[Helvetica]"><?php echo $step['num']; ?></span>
        </div>
        <p class="text-[#767C8C] text-[15px] font-[Montserrat] font-medium"><?php echo $step['label']; ?></p>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- Step cards: Row 2 (2 cols) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <?php
      $row2 = [
    [
        'num'   => 'STEP 04',
        'label' => 'Introduction & Approval',
        'image' => get_template_directory_uri() . '/assets/images/step4.png'
    ],
    [
        'num'   => 'STEP 05',
        'label' => 'Production Begins',
        'image' => get_template_directory_uri() . '/assets/images/step5.png'
    ],
];
      foreach ($row2 as $step) : ?>
      <div class="bg-white border border-[#E8EAEF] rounded-2xl px-6 py-6 shadow-sm">
        <div class="flex items-center gap-3 mb-5">
          <div class="w-9 h-9 bg-[#F0F1F5] rounded-full flex items-center justify-center flex-shrink-0">
             <img
        src="<?php echo esc_url($step['image']); ?>"
        alt="<?php echo esc_attr($step['label']); ?>"
        class="w-5 h-5 object-contain"
    >
          </div>
          <span class="text-[#19191A] text-[18px] font-bold font-[Helvetica]"><?php echo $step['num']; ?></span>
        </div>
        <p class="text-[#767C8C] text-[15px] font-[Montserrat] font-medium"><?php echo $step['label']; ?></p>
      </div>
      <?php endforeach; ?>
    </div>
 
  </div>
</section>
<!-- ========= STEP 01: REGISTER ========= -->
<section class="bg-[#142F32] text-white py-16 px-4 sm:px-6 lg:px-12">
  <div class="reveal max-w-7xl mx-auto">
 
    <!-- Step label -->
    <div class="flex mb-6">
      <div class="relative group">
        <div class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]"></div>
        <div class="flex items-center gap-3 px-5 py-2 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
          <span class="w-2 h-2 bg-white rounded-full"></span>
          <span class="text-white text-[14px] font-[Poppins] font-light tracking-wide">Step 01</span>
        </div>
      </div>
    </div>
 
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
      <!-- Left -->
      <div>
        <h2 class="font-[Helvetica] text-[28px] lg:text-[40px] text-[#FFFFFF] font-medium leading-tight mb-4">
          Register Your <span class="text-[#C8FF00]">Business</span>
        </h2>
        <p class="text-[#C8CCD9] text-[16px] font-[Montserrat] leading-relaxed">
          Create a free account and choose your business type to get started.
        </p>
      </div>
 
      <!-- Right: Two cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 ">
 
        <!-- Tool Owner -->
        <div class="relative group">
          <div class="absolute overflow-hidden bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] rounded-[20px] blur-md opacity-50 group-hover:opacity-100 transition duration-700"></div>
          <div class="relative bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] border border-white/5 rounded-[20px] p-6">
            <div class="w-10 h-10 bg-[#C8FF00]/10 rounded-lg border border-[#C8FF00]/30 flex items-center justify-center mb-4 shadow-[0_0_15px_rgba(200,255,0,0.2)]">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/tool owner.png" class="w-[28px] h-[28px] " alt="Icon">
            </div>
            <h4 class="text-[#EBEDF0] font-semibold text-[16px] font-[Poppins] mb-2">Tool Owner</h4>
            <p class="text-[#8A9BB0] text-[14px] font-[Montserrat] leading-relaxed">You already own mould tools and need production.</p>
          </div>
        </div>
 
        <!-- Machine Owner -->
        <div class="relative group">
          <div class="absolute -inset-1 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] rounded-[20px] blur opacity-50 group-hover:opacity-100 transition duration-700"></div>
          <div class="relative bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] border border-white/5 rounded-[20px] p-6">
            <div class="w-10 h-10 bg-[#C8FF00]/10 rounded-lg border border-[#C8FF00]/30 flex items-center justify-center mb-4 shadow-[0_0_15px_rgba(200,255,0,0.2)]">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/machineowner.png" class="w-[28px] h-[28px] " alt="Icon">
            </div>
            <h4 class="text-[#EBEDF0] font-semibold text-[16px] font-[Poppins] mb-2">Machine Owner / Manufacturer</h4>
            <p class="text-[#8A9BB0] text-[14px] font-[Montserrat] leading-relaxed">You have injection moulding machines available and want new work opportunities.</p>
          </div>
        </div>
 
      </div>
    </div>
  </div>
</section>
<!-- ========= STEP 02: UPLOAD REQUIREMENTS ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <div class="text-center mb-14">
      <p class="text-[#C8FF00] font-medium text-[16px] font-[Helvetica] mb-2">Step 02</p>
      <h2 class="font-bold text-[36px] lg:text-[40px] font-[Helvetica] text-[#FFFFFF]">
        Upload Your <span class="text-[#C8FF00]">Requirements</span>
      </h2>
    </div>
 
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 
      <!-- Tool Owners -->
      <div class="bg-[#143E44] border border-white/10 rounded-2xl p-8">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/tool owner.png" class="w-[28px] h-[28px] " alt="Icon">
          </div>
          <h3 class="text-[#EBEDF0] font-semibold text-[18px] font-[Poppins]">Tool Owners Can Upload:</h3>
        </div>
        <ul class="space-y-3">
          <?php foreach (['Tool details', 'Required machine tonnage', 'Material type', 'Annual volumes', 'Drawings / CAD files', 'Urgency level'] as $item) : ?>
          <li class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-[#C8CCD9] text-[15px] font-[Montserrat]"><?php echo $item; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
 
      <!-- Manufacturers -->
      <div class="bg-[#143E44] border border-white/10 rounded-2xl p-8">
        <div class="flex items-center gap-3 mb-6">
          <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/machineowner.png" class="w-[28px] h-[28px] " alt="Icon">
          </div>
          <h3 class="text-[#EBEDF0] font-semibold text-[18px] font-[Poppins]">Manufacturers Can Upload:</h3>
        </div>
        <ul class="space-y-3">
          <?php foreach (['Available machines', 'Clamp tonnage', 'Materials supported', 'Certifications', 'Spare capacity', 'Location'] as $item) : ?>
          <li class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-[#143E44] flex items-center justify-center flex-shrink-0">
              <svg class="w-3 h-3 text-[#C8FF00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            <span class="text-[#C8CCD9] text-[15px] font-[Montserrat]"><?php echo $item; ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
 
    </div>
  </div>
</section>
<!-- ========= STEP 03: REVIEW & MATCH ========= -->
<section class="bg-[#142F32] text-white py-16 px-4 sm:px-6 lg:px-12">
  <div class="reveal max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
 
    <!-- Left -->
    <div>
      <div class="flex mb-6">
        <div class="relative group">
          <div class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]"></div>
          <div class="flex items-center gap-3 px-5 py-2 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
            <span class="w-2 h-2 bg-white rounded-full"></span>
            <span class="text-white text-[14px] font-[Poppins] font-light">Step 03</span>
          </div>
        </div>
      </div>
      <h2 class="font-[Helvetica] text-[28px] lg:text-[40px] text-[#FFFFFF] font-medium leading-tight mb-4">
        We Review & <span class="text-[#C8FF00]">Match</span>
      </h2>
      <p class="text-[#C8CCD9] text-[16px] font-[Montserrat] leading-relaxed">
        Our team reviews listings and enquiries to identify the best manufacturing match based on:
      </p>
    </div>
 
    <!-- Right: Match criteria -->
    <div class="relative group">
      <div class="absolute -inset-1 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] rounded-[24px] blur opacity-50 group-hover:opacity-100 transition duration-1000"></div>
      <div class="relative bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)] border border-white/5 rounded-[24px] p-6">
        <div class="grid grid-cols-2 gap-3">
          <?php
          $criteria = [
            ['num' => '01', 'text' => 'Machine suitability'],
            ['num' => '02', 'text' => 'Material compatibility'],
            ['num' => '03', 'text' => 'Production volumes'],
            ['num' => '04', 'text' => 'Certifications'],
            ['num' => '05', 'text' => 'Lead times'],
            ['num' => '06', 'text' => 'Location'],
            ['num' => '07', 'text' => 'Commercial fit'],
          ];
          foreach ($criteria as $c) : ?>
          <div class="flex items-center gap-3 bg-white/5 rounded-xl px-4 py-3">
            <span class="flex-shrink-0 w-7 h-7 rounded-full border border-white/20 flex items-center justify-center text-[#8A9BB0] text-[11px] font-semibold font-[Poppins]"><?php echo $c['num']; ?></span>
            <span class="text-[#EBEDF0] text-[14px] font-[Montserrat]"><?php echo $c['text']; ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
 
  </div>
</section>
 
<!-- ========= STEP 04: INTRODUCTION ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <div class="text-center mb-14">
      <p class="text-[#C8FF00] font-medium text-[16px] font-[Helvetica] mb-2">Step 04</p>
      <h2 class="font-bold text-[36px] lg:text-[44px] font-[Helvetica] text-[#FFFFFF]">
        Introduction & <span class="text-[#C8FF00]">Enquiry Approval</span>
      </h2>
      <p class="text-[#C8CCD9] text-[16px] font-[Montserrat] mt-4 max-w-2xl mx-auto leading-relaxed">
        Where suitable, we facilitate introductions between both parties. All enquiries may be subject to review to maintain platform quality.
      </p>
    </div>
 
    <!-- 4 items: 2x2 grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-7xl mx-auto">
      <?php foreach (['Initial contact requests', 'Confidential introductions', 'Quote requests', 'Project discussions'] as $item) : ?>
      <div class="flex items-center gap-3 bg-[#143E44] border border-white/10 rounded-xl px-6 py-4" style="border-left: 3px solid rgba(200,255,0,0.4);">
        <svg class="w-5 h-5 text-[#C8FF00] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[#EBEDF0] text-[16px] font-medium font-[Montserrat]"><?php echo $item; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
  </div>
</section>
 
<!-- ========= STEP 05: PRODUCTION BEGINS ========= -->
<section class="reveal py-16 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="bg-[#143E44] border border-white/10 rounded-2xl px-8 py-12 text-center">
      <div class="flex mb-6 justify-center">
      <div class="relative group">
        <div class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]"></div>
        <div class="flex items-center gap-3 px-5 py-2 bg-[#051616]/50 border border-white/10 rounded-2xl backdrop-blur-sm">
          <span class="w-2 h-2 bg-white rounded-full"></span>
          <span class="text-white text-[14px] font-[Poppins] font-light tracking-wide">Step 05</span>
        </div>
      </div>
    </div>
      <h2 class="text-[36px] lg:text-[44px] font-bold font-[Helvetica] text-[#FFFFFF] mb-4">
        Production <span class="text-[#C8FF00]">Begins</span>
      </h2>
      <p class="text-[#C8CCD9] text-[16px] font-[Montserrat] max-w-2xl mx-auto leading-relaxed">
        Once both parties agree terms, production can begin directly or with our continued support depending on project requirements.
      </p>
    </div>
  </div>
</section>
 
<!-- ========= WHY USE OUR PLATFORM ========= -->
<section class="reveal py-20 bg-[#142F32]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
 
    <div class="text-center mb-12">
      <p class="text-[#C8FF00] font-medium text-[16px] font-[Helvetica] mb-2">Why Use Our Platform</p>
      <h2 class="font-bold text-[36px] lg:text-[44px] font-[Helvetica] text-[#FFFFFF]">
        Built For The <span class="text-[#C8FF00]">Industry</span>
      </h2>
    </div>
 
    <!-- 3 rows x 2 cols -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-16 max-w-7xl mx-auto">
      <?php
      $benefits = [
        'Save time sourcing manufacturers', 'Find available machine capacity',
        'Match tools to suitable machines',  'Access new business opportunities',
        'UK and international network',      'Industry focused service',
      ];
      foreach ($benefits as $b) : ?>
      <div class="flex items-center gap-3 bg-[#143E44] border border-white/10 rounded-xl px-6 py-4" style="border-left: 3px solid rgba(200,255,0,0.4);">
        <svg class="w-5 h-5 text-[#C8FF00] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[#EBEDF0] text-[16px] font-medium font-[Montserrat]"><?php echo $b; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
 
    <!-- For Tool Owners + For Manufacturers -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
 
      <!-- Tool Owners CTA -->
      <div class="bg-[#143E44] border border-white/10 rounded-2xl p-8">
        <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center mb-5">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/tool owner.png" class="w-[28px] h-[28px] " alt="Icon">
        </div>
        <h3 class="text-[#C8FF00] text-[18px] font-semibold font-[Helvetica] mb-3">For Tool Owners</h3>
        <p class="text-[#C8CCD9] text-[15px] font-[Montserrat] leading-relaxed mb-6">
          Need production for an existing mould tool? We help connect you with suitable manufacturers quickly.
        </p>
        <a href="<?php echo is_user_logged_in()
    ? admin_url('admin.php?page=ih-user-add-tool')
    : wp_login_url(admin_url('admin.php?page=ih-user-add-tool')); ?>"
   class="inline-flex items-center gap-2 bg-[#C8FF00] text-[#19191A] px-5 py-2.5 rounded-full text-[14px] font-semibold font-[Montserrat] hover:bg-[#b8ef00] transition-colors duration-300">
    List Your Tool
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M17 7H7M17 7v10" />
          </svg>
</a>
      </div>
 
      <!-- Manufacturers CTA -->
      <div class="bg-[#143E44] border border-white/10 rounded-2xl p-8">
        <div class="w-10 h-10 bg-[#1a3d40] border border-[#C8FF00]/20 rounded-lg flex items-center justify-center mb-5">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/machineowner.png" class="w-[28px] h-[28px] " alt="Icon">
        </div>
        <h3 class="text-[#C8FF00] text-[18px] font-semibold font-[Helvetica] mb-3">For Manufacturers</h3>
        <p class="text-[#C8CCD9] text-[15px] font-[Montserrat] leading-relaxed mb-6">
          Have spare machine capacity? List your machines and receive new project opportunities.
        </p>
        <a href="<?php echo is_user_logged_in()
    ? admin_url('admin.php?page=ih-user-add-machine')
    : wp_login_url(admin_url('admin.php?page=ih-user-add-machine')); ?>"
   class="inline-flex items-center gap-2 bg-[#C8FF00] text-[#19191A] px-5 py-2.5 rounded-full text-[14px] font-semibold font-[Montserrat] hover:bg-[#b8ef00] transition-colors duration-300">
    List Your Machine
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17L17 7M17 7H7M17 7v10" />
    </svg>
</a>
      </div>
 
    </div>
  </div>
</section>
 
<!-- ========= FAQ SECTION ========= -->
<section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">
 
  <div class="reveal max-w-4xl mx-auto text-center pb-4">
    <p class="reveal text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest uppercase">FAQ</p>
    <h2 class="text-white text-[24px] md:text-[40px] font-medium font-[Helvetica]">
      Frequently Asked <span class="text-[#C8FF00]">Questions</span>
    </h2>
  </div>
 
  <?php
  $faqs = [
    ['num' => '01', 'q' => 'Is registration free?',             'a' => 'Yes, businesses can register and create a profile on our platform free of charge.'],
    ['num' => '02', 'q' => 'Do you guarantee work?',            'a' => 'We facilitate introductions and matches but cannot guarantee work outcomes, as this depends on the agreement between both parties.'],
    ['num' => '03', 'q' => 'Can I keep my enquiry confidential?','a' => 'Yes, we handle all enquiries with confidentiality and only share relevant details with matched parties after approval.'],
    ['num' => '04', 'q' => 'Do you only work in the UK?',       'a' => 'We primarily focus on UK manufacturers but also support international connections where suitable matches are available.'],
  ];
  foreach ($faqs as $faq) : ?>
  <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">
    <div class="faq-item rounded-2xl bg-[#143E44] border border-white/5 overflow-hidden transition-all duration-300">
      <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
        <div class="flex items-center gap-4 md:gap-6">
          <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
            <?php echo $faq['num']; ?>
          </span>
          <h3 class="text-white text-[16px] md:text-[22px] font-[Helvetica] leading-tight"><?php echo $faq['q']; ?></h3>
        </div>
        <div class="flex-shrink-0 ml-4">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" alt="Toggle"
            class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
            style="transform: rotate(0deg);">
        </div>
      </button>
      <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24 transition-all duration-300">
        <p class="text-[#F0F1F5] text-sm md:text-[18px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
          <?php echo $faq['a']; ?>
        </p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
 
</section>
</main>
<?php get_footer(); ?>



