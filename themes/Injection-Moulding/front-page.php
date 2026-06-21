<?php
/**
 * Front Page Template (Homepage)
 * This template is used when a static front page is set in Settings > Reading
 */

get_header();

?> 
 <main class="w-full lg:pt-[90px]">


    <!-- ================= HERO SECTION start ================= -->
<?php /* ===== NEW Technical Hero (Direction B) ===== */ get_template_part('hero-technical'); ?>
<?php /* Legacy hero preserved but disabled. Change false -> true to restore the original. */ if ( false ) : ?>
<section class="reveal py-16 lg:py-24 bg-[#F5F6FA] bg-[#F5F6FA] md:mt-[40px] mt-12">
      <div class="reveal max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p
          class="inline-flex items-center gap-2 rounded-full bg-[#d8efad] px-5 py-2 text-sm sm:text-base lg:text-lg text-[#474C59] font-[Helvetica] font-light text-[#474C59] tracking-wide">
          <span class="h-2 w-2 rounded-full bg-[maroon]"></span>
          UK's Leading Injection Moulding Platform
        </p>
        <h1
          class="mt-6 max-w-5xl mx-auto text-3xl sm:text-4xl lg:text-6xl font-bold leading-tight text-[#451516] font-[Helvetica] font-bold leading-[1.22] text-[#451516] tracking-widest">
          Connecting Tool Owners
          <br />
          with Reliable Manufacturers
        </h1>
        <p
          class="mt-4 max-w-3xl mx-auto text-base sm:text-lg text-[#333743] leading-relaxed  leading-relaxed font-[Helvetica] ">
          A dedicated platform for businesses who already have mould tools and
          are searching for trusted injection moulding manufacturing partners.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
          <a href="<?php echo esc_url(home_url('/register')); ?>"
            class="reveal font-[Montserrat]  bg-[#153F45] text-white px-5 sm:px-6 py-2.5 sm:py-3 rounded-full text-sm sm:text-base font-medium flex items-center gap-2">Get
            Started
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt="arrow-up-right" class="w-[20px]" /></a>
          <a href="<?php echo esc_url(home_url('/about')); ?>"
            class="reveal font-[Montserrat] bg-white border border-gray-300 text-[#19191A] px-5 sm:px-6 py-2.5 sm:py-3 rounded-full text-sm sm:text-base font-medium flex items-center gap-2">Learn
            More
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right (1).png" alt="arrow-up-right" class="w-[20px]" /></a>
        </div>
        <p class="mt-6 text-sm sm:text-base font-semibold text-[#19191A]  font-[Montserrat]">
          <i class="fa fa-star" style="color: #ffc903"></i>
          <i class="fa fa-star" style="color: #ffc903"></i>
          <i class="fa fa-star" style="color: #ffc903"></i>
          <i class="fa fa-star" style="color: #ffc903"></i>
          <i class="fa fa-star" style="color: #ffc903"></i>
        
          <span class="ml-1">5.0</span>
        </p>
        <p class="text-xs sm:text-sm text-[#767C8C] font-[Poppins]">
          from <span class="text-[#19191A]">800+ reviews</span>
        </p>
      </div>

     <div class="relative relative max-w-7xl mx-auto mt-12 px-4 sm:px-6 lg:px-8">

  <!-- Left Icon -->
  <div class="hidden md:flex absolute -top-16 left-4 md:left-8 lg:left-12 h-10 w-10 md:h-12 md:w-12 items-center justify-center rounded-full bg-[#def0c6]">
    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230820.png" alt="">
  </div>

  <!-- Right Icon -->
  <div class="hidden md:flex absolute -top-16 right-4 md:right-8 lg:right-12 h-10 w-10 md:h-12 md:w-12 items-center justify-center rounded-full bg-[#def0c6]">
    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230816.png" alt="">
  </div>

    </div>
      <!-- Box Section -->

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12 lg:mt-16 ">
        <div
          class="grid smgrid-cols-1 md:grid-cols-5 lg:grid-cols-5 xl:grid-cols-5 xl:flex xl:flex-row xl:items-end lg:items-end md:items-end xl:justify-center h-auto gap-4 mx-auto items-center justify-items-center">

          <div
            class="w-full max-w-[340px] xl:w-[250px] h-[260px] sm:h-[300px] xl:h-[344px] rounded-[24px] overflow-hidden shrink-0">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Frame 2147230810.png" class="w-full h-full object-cover" />
          </div>

          <div
            class="w-full max-w-[340px] xl:w-[194px] bg-[#153F45] h-[200px] sm:h-[230px] xl:h-[252px] rounded-[2rem] p-6 flex flex-col items-center justify-center text-center  shrink-0">
            <h3 class="text-white text-xl sm:text-2xl lg:text-3xl font-bold font-[Montserrat]">100k+</h3>
            <p class="text-white mt-2 text-sm sm:text-base font-[Montserrat] leading-relaxed">Our Esteemed clients
              and partners</p>
          </div>

<div class="w-full max-w-[320px] bg-white h-[200px] sm:h-[220px] rounded-[2rem] p-6 flex flex-col items-center justify-center text-center border border-gray-100 font-[Montserrat]">

  <!-- Icon -->
  <div class="mb-3">
    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230816 (1).png" class="w-8 sm:w-10" />
  </div>

  <!-- Title + % -->
  <div class="flex items-center gap-2 text-sm sm:text-base font-medium text-[#19191A]">
    <span>Total Projects</span>

    <span class="bg-black text-white rounded-full w-5 h-5 flex items-center justify-center">
      <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" class="w-3 h-3" />
    </span>

    <span class="text-sm sm:text-base font-semibold">95%</span>
  </div>

  <!-- Number -->
  <h3 class="text-xl sm:text-2xl font-bold mt-2 text-[#19191A] ">
    1574+
  </h3>

  <!-- Description -->
  <p class="text-xs sm:text-sm text-gray-500 mt-1">
    Increase of 156 this month
  </p>

</div>
          <div
            class="w-full max-w-[340px] xl:w-[194px] bg-[#D7FF80]/50 h-[200px] sm:h-[230px] xl:h-[252px] rounded-[2rem] p-6 flex flex-col items-center justify-center text-center shrink-0">
            <h3 class="text-xl sm:text-2xl lg:text-3xl font-bold font-[Montserrat]">15+</h3>
            <p class="text-[#19191A] mt-2 text-sm sm:text-base font-[Montserrat] leading-relaxed">Years of <br
                class="hidden xl:block" /> Dedicated Service</p>
          </div>

          <div
            class="w-full max-w-[340px] xl:w-[220px] bg-[#142F32] h-[260px] sm:h-[300px] xl:h-[344px] rounded-[2rem] p-6 flex flex-col items-center justify-center text-center text-white  shrink-0">
            <div class="  rounded-xl mb-3 w-[48px] h-[48px] flex items-center justify-center">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Frame 2147230816 (2).png" class="w-[48px] h-[48px]" />
            </div>
            <p class="text-xs sm:text-sm lg:text-base font-[Montserrat] leading-relaxed">End-to-end solutions
              including sub-assembly, pad printing, packaging, and fulfilment.</p>
          </div>

        </div>
      </div>

</section>
    
<?php endif; /* end legacy hero */ ?>
    <!-- ================= HERO SECTION END ================= -->
  
  <!-- ================= ABOUT SECTION START ================= -->
  <section class="reveal bg-[#F5F6FA] w-full py-8 lg:py-24">
    <div class="grid lg:grid-cols-2 gap-12 items-center max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- LEFT CONTENT -->
      <div class="reveal fade-up text-center lg:text-left">
        <div class="mb-4 flex justify-center lg:justify-start">
          <button
            class="flex items-center gap-2 px-4 py-1.5 bg-[#e6f9c5] rounded-full text-sm sm:text-base text-[#19191A]">
            <span class="w-2 h-2 bg-[#153F45] rounded-full"></span>

            <span class="font-medium font-[Poppins] tracking-wide">
              About us
            </span>
          </button>
        </div>

        <!-- HEADING -->
        <h1
          class="reveal text-3xl sm:text-4xl lg:text-4xl font-medium leading-tight text-[#19191A]  leading-relaxed tracking-wide font-[Helvetica]">
          We connect product owners <br />
          with trusted injection <br />
          moulding manufacturers
        </h1>

        <!-- TEXT -->
        <p
          class="reveal mt-4 text-base sm:text-lg text-[#767C8C] leading-relaxed max-w-2xl mx-auto lg:mx-0 font-medium text-[Montserrat] sm:text-base leading-relaxed">
          Many businesses have product designs or mould tools but do not have
          a production facility. At the same time, many manufacturers have
          capacity but need new clients.
          <br />Our platform brings both sides together. You submit your tool
          or production request. We help you find the right manufacturing
          partner.Our goal is simple. Help businesses start plastic product
          production quickly with reliable manufacturers and clear
          communication.
        </p>

        <!-- STATS -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-8 mt-10 p-6">
          <div class="text-left">
            <h2 class="text-3xl sm:text-3xl font-medium text-[#19191A] font-[Montserrat] tracking-tight mb-3">
              <span class="counter" data-target="100">0</span>k+
            </h2>
            <p class="text-sm sm:text-base text-[#474C59] mt-1 font-medium font-[Montserrat] tracking-wide leading-tight">
              Clients<br />
              Served
            </p>
          </div>

          <div class="relative text-left">
            <div class="hidden md:block absolute left-0 top-1/2 -translate-y-1/2 h-20 border-l border-slate-300"></div>

            <div class="flex flex-col items-start md:px-8">
              <h2 class="text-3xl sm:text-3xl font-medium text-[#19191A] font-[Montserrat] tracking-tight mb-3">
                <span class="counter" data-target="500">0</span>+
              </h2>
              <p class="text-sm sm:text-base text-[#474C59] mt-1 font-medium font-[Montserrat] tracking-wide leading-tight ">
                Manufacturing<br />
                Partners
              </p>
            </div>
          </div>

          <div class="relative text-left">
            <div class="hidden md:block absolute left-0 top-1/2 -translate-y-1/2 h-20 border-l border-slate-300"></div>

            <div class="flex flex-col items-start md:pl-12">
              <h2 class="text-3xl sm:text-3xl font-medium text-[#19191A] font-[Montserrat] tracking-tight mb-3">
                <span class="counter" data-target="15">0</span>+
              </h2>
              <p class="text-sm sm:text-base text-[#474C59] mt-1 font-medium font-[Montserrat] tracking-wide leading-tight text-left">
                Years<br />
                Experience
              </p>
            </div>
          </div>
        </div>

        <!-- BUTTONS -->
        <div class="mt-6 sm:mt-8 flex flex-wrap justify-center lg:justify-start gap-3 sm:gap-4">
          <a href="<?php echo site_url('/register'); ?>"
            class="reveal bg-[#153F45] text-white px-6 py-3 rounded-full text-base font-semibold flex items-center gap-2">
            Get Started
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt="arrow-up-right" class="w-[20px]" />
          </a>
          <a href="<?php echo site_url('/how-it-work'); ?>"
            class="reveal bg-white border border-gray-300 text-[#19191A] px-6 py-3 rounded-full text-base font-medium flex items-center gap-2 font-[Montserrat]">
            Learn More
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right (1).png" alt="arrow-up-right" class="w-[20px]" />
</a>
        </div>
      </div>

      <!-- RIGHT SIDE -->
      <div class="reveal relative w-full max-w-2xl mx-auto aspect-square flex items-center justify-center">
        <!-- IMAGE -->
        <div class="reveal relative z-10 flex items-center justify-center">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/rebort.png" alt="Robot"
            class="w-full h-full object-contain mix-blend-multiply opacity-90" />

          <!-- blur effect -->
          <div class="absolute bottom-6 w-full h-16 sm:h-24 bg-green-900/10 blur-xl"></div>
        </div>

        <!-- TOP RIGHT CARD -->
        <div
          class="reveal absolute top-4 right-2 sm:top-6 sm:right-2 md:top-8 md:right-0 z-20 w-[140px] sm:w-[160px] md:w-[200px] h-[160px] sm:h-[170px] md:h-[210px] bg-[#C8F35A] rounded-[25px] sm:rounded-[30px] md:rounded-[40px] p-4 sm:p-5 md:p-6 flex flex-col items-center justify-center text-center shadow-lg">
          <h2 class="lg:text-[40px] text-[#19191A] md:text-[24px] text-[18px] font-semibold font-[Montserrat]">
            99%
          </h2>

          <p class="text-xs md:text-[16px] font-semibold font-[Montserrat] mt-1 leading-tight">
            client<br />satisfaction rate
          </p>

          <button
            class="lg:mt-7 mt-3 px-[2px] py-2 lg:w-[170px] w-[100px] font-semibold font-[Montserrat] border border-gray-900 rounded-full text-[8px] sm:text-[9px] md:text-[16px] tracking-wide uppercase">
            Proven Results
          </button>
        </div>

        <!-- BOTTOM LEFT CARD -->
        <div
          class="reveal absolute bottom-4 left-2 sm:bottom-6 sm:left-4 md:bottom-8 md:left-0 z-20 w-[160px] sm:w-[160px] md:w-[200px] h-[170px] sm:h-[190px] md:h-[215px] bg-[#143E44] text-white rounded-[25px] sm:rounded-[30px] md:rounded-[40px] px-5 flex flex-col items-center justify-center text-center shadow-lg">
          <h2
            class="text-sm sm:text-lg font-semibold font-[Montserrat] tracking-wide leading-tight">
            Highly Responsive
          </h2>

          <p class="lg:text-[16px] mb-6 md:text-[16px] text-[8px] font-regular font-[Montserrat] tracking-wide">
            support<br />available
          </p>

          <button
            class="px-[6px] py-2 lg:w-[150px] w-[100px] border border-white/40 rounded-full lg:text-[16px] md:text-[16px] text-[9px] font-medium font-[Montserrat] tracking-wide uppercase">
            Always Here
          </button>
        </div>
      </div>
    </div>
  </section>
  <!-- ================= ABOUT SECTION END ================= -->
<!-- ================= Who We Help section start ================= -->
  
  <section class="reveal bg-[#142F32] py-16 lg:py-24">

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

    <!-- TOP TEXT -->
    <p class="text-[#C8FF00] text-sm sm:text-base font-medium mb-3 tracking-wide">
      Who We Help
    </p>

    <!-- HEADING -->
    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white leading-tight max-w-5xl mx-auto">
      Built for Every Stage of
      <span class="text-[#C8FF00]">Manufacturing</span>
    </h2>

    <!-- DESCRIPTION -->
    <p class="mt-4 sm:mt-6 text-base sm:text-lg text-gray-300 max-w-2xl mx-auto leading-relaxed">
      Whether you're scaling production or exploring new markets, our
      platform is designed to serve your unique needs.
    </p>

    <!-- CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-10 lg:mt-14">

      <!-- CARD -->
      <div class="bg-[#143E44] rounded-2xl p-6 lg:p-8 text-left transition hover:scale-[1.03]">

        <div class="w-12 h-12 bg-[#1f5b5f] rounded-lg flex items-center justify-center mb-5">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Tool Owners icon.png" class="w-6" />
        </div>

        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
          Tool Owners
        </h3>

        <p class="text-sm sm:text-base text-gray-300 leading-relaxed">
          Own existing moulds? We'll connect you with reliable manufacturers
          to run your tools and deliver quality parts on time and within
          budget.
        </p>

      </div>

      <!-- CARD -->
      <div class="bg-[#143E44] rounded-2xl p-6 lg:p-8 text-left transition hover:scale-[1.03]">

        <div class="w-12 h-12 bg-[#1f5b5f] rounded-lg flex items-center justify-center mb-5">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Startups & Product Developers icon.png" class="w-6" />
        </div>

        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
          Startups & Product Developers
        </h3>

        <p class="text-sm sm:text-base text-gray-300 leading-relaxed">
          From prototyping to full production, we help innovators find the
          right manufacturing partner to bring their product ideas to
          market.
        </p>

      </div>

      <!-- CARD -->
      <div class="bg-[#143E44] rounded-2xl p-6 lg:p-8 text-left transition hover:scale-[1.03]">

        <div class="w-12 h-12 bg-[#1f5b5f] rounded-lg flex items-center justify-center mb-5">
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Overseas Tool Owners icon.png" class="w-6" />
        </div>

        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
          Overseas Tool Owners
        </h3>

        <p class="text-sm sm:text-base text-gray-300 leading-relaxed">
          Looking to reshore your manufacturing to the UK? We match
          international tool owners with trusted domestic manufacturers.
        </p>

      </div>

    </div>

  </div>

</section>
  <!-- ================= Who We Help section end ================= -->
<!-- ================= Our Capabilities start ================= -->
  <section class="bg-[#00191C] text-white py-16 px-4 sm:px-6 lg:px-12">
    <div class="reveal max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-10 items-center">
      <!-- LEFT CONTENT -->
      <div>
        <!-- Tag box-->
        <div class="reveal flex mb-2">
          <div class="relative group">
            <div
              class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]">
            </div>

            <div
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#051616]/60 border border-white/10 text-sm sm:text-base rounded-2xl backdrop-blur-sm">
              <span class="w-2 h-2 bg-white rounded-full"></span>

              <span class="text-white font-[Poppins] font-light tracking-wide ">
                Our Capabilities
              </span>
            </div>
          </div>
        </div>

        <!-- Heading -->
      
        <h2 class="text-3xl sm:text-4xl lg:text-4xl font-semibold leading-tight font-[Montserrat] tracking-widest pt-2">
        Production Solutions <br class="hidden sm:block" />
        That Scale
       </h2>
           <!-- Description -->
         <p class="mt-4 text-base sm:text-lg text-[#C8CCD9] leading-relaxed font-[Poppins] max-w-lg font-regular">
        Our platform connects businesses with a growing network of injection
          moulding companies offering flexible production capabilities.
          Whether you require short production runs or medium-scale
          manufacturing, we match your tool with the right capacity.
        </p>
        <!-- CARDS -->
        <div class="reveal mt-8 space-y-4">
          <!-- CARD -->

          <div
            class="relative bg-[#062f2f] rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
            <!-- Neon Line -->
            <div
              class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
            </div>
            <!-- Border Glow -->

            <div
              class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(190,242,100,0.2)]">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Injection Moulding icon.png" alt="" class="w-[24px] shadow-md" />
            </div>
            <h3 class="text-lg sm:text-xl font-semibold text-[#EBEDF0] py-2">Injection Moulding</h3>
          <p class="text-sm sm:text-base leading-relaxed text-[#FFFFFF]-60% text-medium">
            Full-service plastic injection moulding from prototyping to
            production with precision and quality.
          </p>
          </div>
          <!-- CARD -->

          <div
            class="reveal relative bg-[#062f2f] rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
            <!-- Neon Line -->
            <div
              class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
            </div>
            <!-- Border Glow -->

            <div
              class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(190,242,100,0.2)]">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Short Run Production.png" alt="" class="w-[24px] shadow-md" />
            </div>
            <h3 class="text-lg sm:text-xl font-semibold text-[#EBEDF0] py-2"> Short Run Production</h3>
          <p class="text-sm sm:text-base leading-relaxed text-[#FFFFFF]-60% text-medium">
             Cost-effective short run production services ideal for
              prototyping and market testing.
          </p>
          
          </div>
          <!-- CARD -->

          <div
            class="reveal relative bg-[#062f2f] rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
            <!-- Neon Line -->
            <div
              class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
            </div>
            <!-- Border Glow -->

            <div
              class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(190,242,100,0.2)]">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Medium Volume Production.png" alt="" class="w-[24px] shadow-md" />
            </div>
            <h3 class="text-lg sm:text-xl font-semibold text-[#EBEDF0] py-2">  Medium Volume Production</h3>
          <p class="text-sm sm:text-base leading-relaxed text-[#FFFFFF]-60% text-medium">
              Scalable manufacturing solutions for medium volume production
              requirements with consistent quality.
          </p>
        
          </div>
          <!-- CARD -->

          <div
            class="reveal relative bg-[#062f2f] rounded-[28px] p-6 bg-[linear-gradient(to_top,rgba(200,255,0,0.15)_0%,rgba(97,97,97,0.09)_60%,transparent_100%)]">
            <!-- Neon Line -->
            <div
              class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[90px] h-[1.5px] bg-[linear-gradient(90deg,transparent,#bef264,transparent)] shadow-[0_0_8px_rgba(190,242,100,0.5)]">
            </div>
            <!-- Border Glow -->

            <div
              class="w-10 h-10 bg-[#BEF264]/10 border border-[#BEF264] rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(190,242,100,0.2)]">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Assembly & Packaging.png" alt="" class="w-[24px] shadow-md" />
            </div>
            <h3 class="text-lg sm:text-xl font-semibold text-[#EBEDF0] py-2">Assembly & Packaging</h3>
          <p class="text-sm sm:text-base leading-relaxed text-[#FFFFFF]-60% text-medium">
              Complete assembly and packaging services to deliver
              ready-to-market products.
          </p>
          
          </div>
        </div>
      </div>

      <!-- RIGHT IMAGE -->
      <div class="reveal relative flex justify-center lg:justify-start lg:-mt-[500px]">
        <div class="relative ">
          <!-- Image -->
          <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Our Capabilities.png" alt="Factory Team" class="w-full h-full object-cover" />

          <!-- Glow -->
          <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 w-3/4 h-16 bg-lime-400/20 blur-2xl rounded-full">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= Our Capabilities end ================= -->
<!-- ================= why chose us start ================= -->
<section class="reveal bg-[#F5F6FA] py-16 sm:py-20 lg:py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- TOP TEXT -->
      <p
        class="inline-flex items-center gap-2 rounded-full bg-[#d8efad] px-5 py-1 text-sm sm:text-base font-[Helvetica] font-light text-[#19191A] tracking-wide">
        <span class="h-2 w-2 rounded-full bg-[#19191A]"></span>
        Why Us
      </p>

      <!-- HEADING -->
      <h2
        class="mt-4 text-3xl sm:text-4xl lg:text-5xl tracking-wide font-medium font-[Helvetica] text-[#19191A] leading-tight mx-auto pt-3 tracking-wide">
        Why Choose mouldinjection.co.uk
      </h2>

      <!-- DESCRIPTION -->
      <p
        class="reveal text-[#767C8C] font-medium font-[Montserrat] mt-4 sm:mt-6 text-base sm:text-lg max-w-7xl mx-auto leading-relaxed">
        We focus on building strong connections between tool owners and
        reliable injection moulding manufacturers with transparent
        communication.
      </p>

      <!-- CARDS -->
      <div class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mt-5 sm:mt-14">
        <!-- CARD 1 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                 mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/location icon.svg'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/location icon.svg'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
          <h3 class="text-lg sm:text-xl font-semibold text-[#15181F] group-hover:text-white mb-2">
          UK Based Production Network
        </h3>

        <p class="text-sm sm:text-base text-[#474C59] group-hover:text-white leading-relaxed">
          Access a curated network of UK-based injection moulding manufacturers.
        </p>
        </div>
        <!-- CARD 2 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/setting\ icon.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/setting\ icon.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
    
          <h3 class="text-lg sm:text-xl font-semibold text-[#15181F] group-hover:text-white mb-2">
          Flexible Production Volumes
        </h3>

        <p class="text-sm sm:text-base text-[#474C59] group-hover:text-white leading-relaxed">
          From 100 units to 100,000+ — find the right partner for any volume.
        </p>
        </div>
        <!-- CARD 3 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                 mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Fast\ Manufacturing\ Turnaround.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Fast\ Manufacturing\ Turnaround.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
           <h3 class="text-lg sm:text-xl font-semibold text-[#15181F] group-hover:text-white mb-2">
          Fast Manufacturing Turnaround
        </h3>

        <p class="text-sm sm:text-base text-[#474C59] group-hover:text-white leading-relaxed">
          Rapid matching and production start to minimize your time to market.
        </p>
        </div>

        <!-- CARD 4 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Transparent\ Pricing.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Transparent\ Pricing.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
           <h3 class="text-lg sm:text-xl font-semibold text-[#15181F] group-hover:text-white mb-2">
          Transparent Pricing
        </h3>

        <p class="text-sm sm:text-base text-[#474C59] group-hover:text-white leading-relaxed">
          Clear, upfront pricing with no hidden costs or surprise fees.
        </p>
        </div>
        <!-- CARD 5 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Reliable\ Partners.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Reliable\ Partners.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                "></div>
          </div>

          <!-- TITLE -->
           <h3 class="text-lg sm:text-xl font-semibold text-[#15181F] group-hover:text-white mb-2">
          Reliable Partners
        </h3>

        <p class="text-sm sm:text-base text-[#474C59] group-hover:text-white leading-relaxed">
          Vetted manufacturing partners with proven track records of quality.
        </p>
        </div>
        <!-- CARD 6 -->
        <div
          class="group relative bg-[#FFFFFF] p-8 rounded-xl sm:rounded-2xl sm:p-6 lg:p-8 text-left shadow-lg transition-all duration-300 ease-in-out hover:bg-[#0f3032] cursor-pointer">
          <!-- ICON -->
          <div
            class="w-12 h-12 flex items-center justify-center rounded-lg bg-[#F0F1F5] mb-6 transition-colors duration-300 group-hover:bg-[#fbfbfb1f]">
            <div class="w-6 h-6 bg-black group-hover:bg-[#d4ff00] transition-colors duration-300" style="
                  mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Quality\ Assured.png'); 
        -webkit-mask-image: url('<?php echo get_template_directory_uri(); ?>/assets/images/icons/Quality\ Assured.png'); 
        mask-repeat: no-repeat; 
        mask-size: contain; 
        -webkit-mask-size: contain; 
        mask-position: center;
        -webkit-mask-position: center;
                ">
              
        </div>
        </div>

          <!-- TITLE -->
        <h3 class="text-lg sm:text-xl font-semibold text-[#15181F] group-hover:text-white mb-2">
          Quality Assured
        </h3>

        <p class="text-sm sm:text-base text-[#474C59] group-hover:text-white leading-relaxed">
          Every manufacturer meets strict quality standards and certifications.
        </p>
      </div>
  </div>
</section>
  <!-- ================= why chose us end ================= -->
  <!-- ================= Industries We Serve start ================= -->
  <section class="reveal bg-[#00191C] py-24 sm:py-20  mx-auto text-center">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- TOP TEXT -->
      <p class="reveal text-[#C8FF00] font-[Helvetica] font-medium text-lg sm:text-xl mb-3 sm:mb-4 tracking-wide">
        Industries
      </p>
 
      <!-- HEADING -->
      <h2 class="text-2xl sm:text-3xl lg:text-[40px] font-medium text-white leading-tight">
      Industries <span class="text-[#C8FF00]">We Serve</span>
    </h2>

      <!-- DESCRIPTION -->
      <p
       class="text-[#C8CCD9] mt-4 sm:mt-6 text-sm sm:text-base max-w-4xl mx-auto leading-relaxed">
        We provide high-precision plastic injection moulding solutions for multiple industries.
        Our advanced technology and experienced team help businesses produce reliable and durable plastic components.
      </p>

      <!-- CARDS -->


      <div
        class="reveal grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 gap-y-28 mt-10 sm:mt-14 justify-items-center pb-5">

        <div
          class="relative w-full max-w-[421px] xs:max-w-sm mx-auto group cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
          <div class="overflow-hidden rounded-[20px]">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Automotive Industry.png" alt="Automotive"
              class="w-full h-[260px] object-cover transition-transform duration-500 group-hover:scale-105" />
          </div>
          <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-16 w-[92%] bg-gray-200 rounded-[20px] px-3 py-6 shadow-lg text-center flex flex-col justify-center min-h-[100px] z-10">
            <h3 class="text-[20px] font-[Montserrat] font-semibold text-[#17012C] mb-3 tracking-tight leading-tight">
              Automotive Industry
            </h3>
            <p class="text-[#474C59] text-[14px] font-[Montserrat] font-medium leading-relaxed">
              We Manufacture Durable And High Precision Plastic Components Used In Automotive Interiors, Trims, And
              Functional Vehicle Parts.
            </p>
          </div>
        </div>

        <div
          class="relative w-full max-w-[421px] xs:max-w-sm mx-auto group cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
          <div class="overflow-hidden rounded-[20px]">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Medical Equipment.png" alt="Medical Equipment"
              class="w-full h-[260px] object-cover transition-transform duration-500 group-hover:scale-105" />
          </div>
          <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-16 w-[92%] bg-gray-200 rounded-[20px] px-5 py-6 shadow-lg text-center flex flex-col justify-center min-h-[100px] z-10">
            <h3 class="text-[20px] font-[Montserrat] font-semibold text-[#17012C] mb-3 tracking-tight leading-tight">
              Medical Equipment</h3>
            <p class="text-[#474C59] text-[14px] font-[Montserrat] font-medium leading-relaxed">
              Our moulding technology produces hygienic and reliable plastic components used in healthcare and medical
              devices.</p>
          </div>
        </div>

        <div
          class="relative w-full max-w-[421px] xs:max-w-sm mx-auto group cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
          <div class="overflow-hidden rounded-[20px]">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Packaging Industry.png" alt="Packaging"
              class="w-full h-[260px] object-cover transition-transform duration-500 group-hover:scale-105" />
          </div>
          <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-16 w-[92%] bg-gray-200 rounded-[20px] px-5 py-6 shadow-lg text-center flex flex-col justify-center min-h-[100px] z-10">
            <h3 class="text-[20px] font-[Montserrat] font-semibold text-[#17012C] mb-3 tracking-tight leading-tight">
              Packaging Industry</h3>
            <p class="text-[#474C59] text-[14px] font-[Montserrat] font-medium leading-relaxed">
              We develop strong and lightweight plastic packaging components designed for safety and durability.</p>
          </div>
        </div>

        <div
          class="relative w-full max-w-[421px] xs:max-w-sm mx-auto group cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
          <div class="overflow-hidden rounded-[20px]">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Consumer Products.png" alt="Consumer"
              class="w-full h-[260px] object-cover transition-transform duration-500 group-hover:scale-105" />
          </div>
          <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-16 w-[92%] bg-gray-200 rounded-[20px] px-5 py-6 shadow-lg text-center flex flex-col justify-center min-h-[100px] z-10">
            <h3 class="text-[20px] font-[Montserrat] font-semibold text-[#17012C] mb-3 tracking-tight leading-tight">
              Consumer Products</h3>
            <p class="text-[#474C59] text-[14px] font-[Montserrat] font-medium leading-relaxed">
              We manufacture plastic parts used in household products and everyday consumer goods.</p>
          </div>
        </div>

        <div
          class="relative w-full max-w-[421px] xs:max-w-sm mx-auto group cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
          <div class="overflow-hidden rounded-[20px]">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Electronics Industry.png" alt="Electronics"
              class="w-full h-[260px] object-cover transition-transform duration-500 group-hover:scale-105" />
          </div>
          <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-16 w-[92%] bg-gray-200 rounded-[20px] px-5 py-6 shadow-lg text-center flex flex-col justify-center min-h-[00px] z-10">
            <h3 class="text-[20px] font-[Montserrat] font-semibold text-[#17012C] mb-3 tracking-tight leading-tight">
              Electronics Industry</h3>
            <p class="text-[#474C59] text-[14px] font-[Montserrat] font-medium leading-relaxed">
              Precision plastic components used in electronic devices, enclosures, and protective casings.</p>
          </div>
        </div>

        <div
          class="relative w-fullmax-w-[421px] xs:max-w-sm mx-auto group cursor-pointer transition-transform duration-300 hover:scale-[1.01]">
          <div class="overflow-hidden rounded-[20px]">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/Industrial Equipment.png" alt="Industrial"
              class="w-full h-[260px] object-cover transition-transform duration-500 group-hover:scale-105" />
          </div>
          <div
            class="absolute left-1/2 -translate-x-1/2 -bottom-16 w-[92%] bg-gray-200 rounded-[20px] px-5 py-6 shadow-lg text-center flex flex-col justify-center min-h-[100px] z-10">
            <h3 class="text-[20px] font-[Montserrat] font-semibold text-[#17012C] mb-3 tracking-tight leading-tight">
              Industrial Equipment</h3>
            <p class="text-[#474C59] text-[14px] font-[Montserrat] font-medium leading-relaxed">
              Heavy-duty moulded plastic parts designed for industrial machines and manufacturing equipment.</p>
          </div>
        </div>

      </div>
    </div>
  </section>
  
  <!-- ================= Industries We Serve end ================= -->
   
  <!-- ================= Who We Help section start ================= -->
<section class="bg-[#142F32] py-16 sm:py-20 lg:py-24">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

    <!-- TOP TEXT -->
    <p class="text-[#C8FF00] font-[Helvetica] font-medium text-lg sm:text-xl mb-3 sm:mb-4 tracking-wide">
      Process
    </p>

    <!-- HEADING -->
    <h2 class="text-2xl sm:text-3xl lg:text-[40px] font-semibold text-white leading-tight">
      How It <span class="text-[#C8FF00]">Works</span>
    </h2>

    <!-- DESCRIPTION -->
    <p class="text-[#C8CCD9] mt-4 sm:mt-6 text-sm sm:text-base max-w-xl mx-auto leading-relaxed">
      Three simple steps to connect with the perfect manufacturing partner for your project.
    </p>

    <!-- CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-10 sm:mt-14">

      <!-- CARD -->
      <div class="bg-[#143E44] rounded-2xl p-6 lg:p-8 text-left shadow-lg hover:scale-[1.02] transition">

        <div class="flex items-center justify-between mb-6">
          <!-- ICON -->
          <div class="w-12 h-12 bg-[#1f5b5f] rounded-lg flex items-center justify-center">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Submit Your Details.png" class="w-6" />
          </div>

          <!-- NUMBER -->
          <span class="text-3xl sm:text-4xl font-semibold text-white/30">
            01
          </span>
        </div>

        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
          Submit Your Details
        </h3>

        <p class="text-[#C8CCD9] text-sm sm:text-base leading-relaxed">
         Share your mould tool information through our platform including material requirements, production volumes,
            and technical specifications.
        </p>
      </div>

      <!-- CARD -->
      <div class="bg-[#143E44] rounded-2xl p-6 lg:p-8 text-left shadow-lg hover:scale-[1.02] transition">

        <div class="flex items-center justify-between mb-6">
          <div class="w-12 h-12 bg-[#1f5b5f] rounded-lg flex items-center justify-center">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/We Match You.png" class="w-6" />
          </div>

          <span class="text-3xl sm:text-4xl font-semibold text-white/30">
            02
          </span>
        </div>

        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
          We Match You
        </h3>

        <p class="text-[#C8CCD9] text-sm sm:text-base leading-relaxed">
           We review your information and identify injection moulding companies with the right machine capacity, expertise, and location for your project.
        </p>
      </div>

      <!-- CARD -->
      <div class="bg-[#143E44] rounded-2xl p-6 lg:p-8 text-left shadow-lg hover:scale-[1.02] transition">

        <div class="flex items-center justify-between mb-6">
          <div class="w-12 h-12 bg-[#1f5b5f] rounded-lg flex items-center justify-center">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/Begin Production.png" class="w-6" />
          </div>

          <span class="text-3xl sm:text-4xl font-semibold text-white/30">
            03
          </span>
        </div>

        <h3 class="text-lg sm:text-xl font-semibold text-white mb-2">
          Begin Production
        </h3>

        <p class="text-[#C8CCD9] text-sm sm:text-base leading-relaxed">
           Once the right manufacturer is identified, production arrangements begin with clear communication, timelines, and quality agreements in place.
        </p>
      </div>

    </div>
  </div>
</section>
    <!-- ================= Who We Help section end ================= -->
     <!-- ================= Listing section start================= -->
       <?php
/**
 * PASTE THIS CODE IN front-page.php
 * 
 * Place SECTION 1 (Available Machines) after the "How It Works" section
 * Place SECTION 2 (Available Tools & Moulds) after Section 1
 * 
 * Both sections go BEFORE the FAQ section
 */


global $wpdb;

$approved_machines = [];
$approved_tools    = [];
$front_listings    = get_transient( 'im_home_featured_listings_v1' );

if ( is_array( $front_listings ) ) {
    $approved_machines = $front_listings['machines'] ?? [];
    $approved_tools    = $front_listings['tools'] ?? [];
} else {
    $machines_table = $wpdb->prefix . 'ih_machines';
    $tools_table    = $wpdb->prefix . 'ih_tools';
    $requests_table = $wpdb->prefix . 'ih_requests';

    $has_machines = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $machines_table ) ) === $machines_table;
    $has_tools    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tools_table ) ) === $tools_table;
    $has_requests = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $requests_table ) ) === $requests_table;

    if ( $has_machines ) {
        $approved_machines = $wpdb->get_results(
            "SELECT DISTINCT m.* FROM {$machines_table} m
             " . ( $has_requests ? "LEFT JOIN {$requests_table} r ON r.listing_id = m.id AND r.listing_type = 'machine' AND LOWER(TRIM(r.status)) = 'approved'" : '' ) . "
             WHERE m.available = 1" . ( $has_requests ? " OR r.id IS NOT NULL" : '' ) . "
             ORDER BY m.id DESC LIMIT 6",
            ARRAY_A
        ) ?: [];
    }

    if ( $has_tools ) {
        $approved_tools = $wpdb->get_results(
            "SELECT DISTINCT t.* FROM {$tools_table} t
             " . ( $has_requests ? "LEFT JOIN {$requests_table} r ON r.listing_id = t.id AND r.listing_type = 'tool' AND LOWER(TRIM(r.status)) = 'approved'" : '' ) . "
             WHERE t.available = 1" . ( $has_requests ? " OR r.id IS NOT NULL" : '' ) . "
             ORDER BY t.id DESC LIMIT 6",
            ARRAY_A
        ) ?: [];
    }

    set_transient( 'im_home_featured_listings_v1', [
        'machines' => $approved_machines,
        'tools'    => $approved_tools,
    ], 5 * MINUTE_IN_SECONDS );
}

$default_img = 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&q=80';
?>

<!-- ================= AVAILABLE MACHINES SECTION START ================= -->
<?php if ( ! empty( $approved_machines ) ) : ?>
<section class="reveal bg-[#F5F6FA] py-16 sm:py-20 lg:py-24">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="text-center mb-10 sm:mb-14">
      <p class="inline-flex items-center gap-2 rounded-full bg-[#d8efad] px-5 py-1 text-sm sm:text-base font-[Helvetica] font-light text-[#19191A] tracking-wide mb-4">
        <span class="h-2 w-2 rounded-full bg-[#153F45]"></span>
        Are Listed
      </p>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-medium font-[Helvetica] text-[#19191A] leading-tight tracking-wide mt-3">
        Available <span class="text-[#153F45]">Machines</span>
      </h2>
      <p class="mt-4 text-base sm:text-lg text-[#767C8C] max-w-2xl mx-auto font-[Montserrat] leading-relaxed">
        Browse our latest approved injection moulding machines ready for production.
      </p>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ( $approved_machines as $machine ) :
        $img        = ! empty( $machine['image_1'] ) ? esc_url( $machine['image_1'] ) : $default_img;
        $title      = esc_html( $machine['title'] ?? 'Machine' );
        $type       = esc_html( $machine['machine_type'] ?? '' );
        $location   = esc_html( $machine['location'] ?? '' );
        $tonnage    = ! empty( $machine['clamping_force'] ) ? esc_html( $machine['clamping_force'] ) . ' T' : '';
        $hours      = ! empty( $machine['operating_hours'] ) ? esc_html( $machine['operating_hours'] ) . ' hrs' : '';
        $machine_id = esc_html( $machine['machine_id'] ?? ( 'MCH-' . str_pad( $machine['id'], 5, '0', STR_PAD_LEFT ) ) );
        $msg_url    = admin_url( 'admin.php?page=ih-user-messages&listing_id=' . intval( $machine['id'] ) . '&listing_type=machine' );
        $det_url = home_url( '/machine/?id=' . intval( $machine['id'] ) );
      ?>
      <div class="group bg-[#143E44] rounded-2xl overflow-hidden border border-white/5 hover:border-[#C8FF00]/30 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#C8FF00]/5 transition-all duration-300 flex flex-col">
 
        <!-- Image -->
        <div class="relative overflow-hidden h-[200px] flex-shrink-0">
          <img src="<?php echo $img; ?>"
               onerror="this.src='<?php echo esc_js($default_img); ?>'"
               alt="<?php echo $title; ?>"
               class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
          <span class="absolute top-3 left-3 bg-[#C8FF00] text-[#0a1f1f] text-xs font-bold px-3 py-1 rounded-full">
            Available Now
          </span>
          <?php if ( $machine_id ) : ?>
          <span class="absolute top-3 right-3 bg-black/60 text-white text-xs font-semibold px-2 py-1 rounded-lg backdrop-blur-sm">
            <?php echo $machine_id; ?>
          </span>
          <?php endif; ?>
        </div>
 
        <!-- Content -->
        <div class="p-5 flex flex-col flex-1">
 
          <!-- Title + Type -->
          <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="text-base sm:text-lg font-bold text-white font-[Montserrat] leading-snug">
              <?php echo $title; ?>
            </h3>
            <?php if ( $type ) : ?>
            <span class="flex-shrink-0 bg-white/10 text-[#C8CCD9] text-xs font-medium px-2.5 py-1 rounded-full whitespace-nowrap">
              <?php echo $type; ?>
            </span>
            <?php endif; ?>
          </div>
 
          <!-- Meta info -->
          <div class="grid grid-cols-2 gap-3 mb-2 pb-3 border-b border-white/10">
            <?php if ( $location ) : ?>
            <div>
              <p class="text-xs text-[#6b7280] font-[Poppins] mb-0.5">Location</p>
              <p class="text-sm font-semibold text-white font-[Montserrat]"><?php echo $location; ?></p>
            </div>
            <?php endif; ?>
            <?php if ( $tonnage ) : ?>
            <div>
              <p class="text-xs text-[#6b7280] font-[Poppins] mb-0.5">Clamping Force</p>
              <p class="text-sm font-semibold text-white font-[Montserrat]"><?php echo $tonnage; ?></p>
            </div>
            <?php endif; ?>
            <?php if ( $hours ) : ?>
            <div>
              <p class="text-xs text-[#6b7280] font-[Poppins] mb-0.5">Operating Hours</p>
              <p class="text-sm font-semibold text-white font-[Montserrat]"><?php echo $hours; ?></p>
            </div>
            <?php endif; ?>
          </div>
 
          <!-- Buttons — always at bottom -->
          <div class="flex gap-2 mt-auto pt-3">
            <a href="<?php echo esc_url( $det_url ); ?>"
               class="flex-1 flex items-center justify-center gap-1.5 bg-[#C8FF00] text-[#0a1f1f] text-sm font-bold font-[Montserrat] px-4 py-2.5 rounded-full hover:bg-[#b8ef00] transition-colors">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 flex-shrink-0"><circle cx="12" cy="12" r="3"/><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/></svg>
              Details
            </a>
            <a href="<?php echo esc_url( $msg_url ); ?>"
               class="flex-1 flex items-center justify-center gap-1.5 border border-[#C8FF00]/40 text-[#C8FF00] text-sm font-semibold font-[Montserrat] px-4 py-2.5 rounded-full hover:bg-[#C8FF00]/10 transition-colors">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 flex-shrink-0"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Message
            </a>
          </div>
 
        </div>
      </div>
 
      <?php endforeach; ?>
    </div>

    <!-- View All button -->
    <div class="text-center mt-10">
      <a href="<?php echo esc_url( home_url( '/machines' ) ); ?>"
         class="inline-flex items-center gap-2 bg-[#153F45] text-white px-7 py-3 rounded-full text-sm sm:text-base font-semibold font-[Montserrat] hover:bg-[#0f2e33] transition-colors">
        View All Machines
        <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png" alt="" class="w-4 sm:w-5" />
      </a>
    </div>

  </div>
</section>
<?php endif; ?>
<!-- ================= AVAILABLE MACHINES SECTION END ================= -->


<!-- ================= AVAILABLE TOOLS & MOULDS SECTION START ================= -->
<?php if ( ! empty( $approved_tools ) ) : ?>
<section class="reveal bg-[#00191C] py-16 sm:py-20 lg:py-24">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <!-- Header -->
    <div class="text-center mb-10 sm:mb-14">
      <p class="text-[#C8FF00] font-[Helvetica] font-medium text-lg sm:text-xl mb-3 tracking-wide">
        Are Listed
      </p>
      <h2 class="text-3xl sm:text-4xl lg:text-5xl font-medium text-white leading-tight">
        Available <span class="text-[#C8FF00]">Tools &amp; Moulds</span>
      </h2>
      <p class="mt-4 text-base sm:text-lg text-[#C8CCD9] max-w-2xl mx-auto font-[Montserrat] leading-relaxed">
        Explore approved mould tools available and ready for injection moulding production.
      </p>
    </div>

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ( $approved_tools as $tool ) :
        $img      = ! empty( $tool['image_1'] ) ? esc_url( $tool['image_1'] ) : $default_img;
        $title    = esc_html( $tool['title'] ?? 'Tool' );
        $type     = esc_html( $tool['mould_type'] ?? '' );
        $material = esc_html( $tool['material'] ?? '' );
        $cavities = ! empty( $tool['cavities'] ) ? esc_html( $tool['cavities'] ) : '';
        $tool_id  = esc_html( $tool['tool_id'] ?? ( 'TL-' . str_pad( $tool['id'], 5, '0', STR_PAD_LEFT ) ) );
        $desc     = ! empty(  $tool['num_cavities'] ) ? wp_trim_words( $tool['part_description'], 12 ) : '';
        $msg_url  = admin_url( 'admin.php?page=ih-user-messages&listing_id=' . intval( $tool['id'] ) . '&listing_type=tool' );
        $det_url = home_url( '/tool/?id=' . intval( $tool['id'] ) );
      ?>
      <div class="group bg-[#143E44] rounded-2xl overflow-hidden border border-white/5 hover:border-[#C8FF00]/30 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#C8FF00]/5 transition-all duration-300 flex flex-col">
 
        <!-- Image -->
        <div class="relative overflow-hidden h-[200px] flex-shrink-0">
          <img src="<?php echo $img; ?>"
               onerror="this.src='<?php echo esc_js($default_img); ?>'"
               alt="<?php echo $title; ?>"
               class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
          <span class="absolute top-3 left-3 bg-[#C8FF00] text-[#0a1f1f] text-xs font-bold px-3 py-1 rounded-full">
            Available Now
          </span>
          <?php if ( $tool_id ) : ?>
          <span class="absolute top-3 right-3 bg-black/60 text-white text-xs font-semibold px-2 py-1 rounded-lg backdrop-blur-sm">
            <?php echo $tool_id; ?>
          </span>
          <?php endif; ?>
        </div>
 
        <!-- Content -->
        <div class="p-5 flex flex-col flex-1">
 
          <!-- Title + Type -->
          <div class="flex items-start justify-between gap-2 mb-3">
            <h3 class="text-base sm:text-lg font-bold text-white font-[Montserrat] leading-snug">
              <?php echo $title; ?>
            </h3>
            <?php if ( $type ) : ?>
            <span class="flex-shrink-0 bg-white/10 text-[#C8CCD9] text-xs font-medium px-2.5 py-1 rounded-full whitespace-nowrap">
              <?php echo $type; ?>
            </span>
            <?php endif; ?>
          </div>
 
          <!-- Description -->
          <?php if ( $desc ) : ?>
          <p class="text-sm text-[#C8CCD9] font-[Montserrat] leading-relaxed mb-3 line-clamp-2">
            <?php echo esc_html( $desc ); ?>
          </p>
          <?php endif; ?>
 
          <!-- Meta info -->
          <div class="grid grid-cols-2 gap-3 mb-2 pb-3 border-b border-white/10">
            <?php if ( $material ) : ?>
            <div>
              <p class="text-xs text-[#6b7280] font-[Poppins] mb-0.5">Material</p>
              <p class="text-sm font-semibold text-white font-[Montserrat]"><?php echo $material; ?></p>
            </div>
            <?php endif; ?>
            <?php
            $num_cav = ! empty( $tool['num_cavities_spec'] ) ? $tool['num_cavities_spec'] : ( ! empty( $tool['num_cavities'] ) ? $tool['num_cavities'] : '' );
            if ( $num_cav ) : ?>
            <div>
              <p class="text-xs text-[#6b7280] font-[Poppins] mb-0.5">Cavities</p>
              <p class="text-sm font-semibold text-white font-[Montserrat]"><?php echo esc_html($num_cav); ?></p>
            </div>
            <?php endif; ?>
          </div>
 
          <!-- Buttons — always at bottom -->
          <div class="flex gap-2 mt-auto pt-3">
            <a href="<?php echo esc_url( $det_url ); ?>"
               class="flex-1 flex items-center justify-center gap-1.5 bg-[#C8FF00] text-[#0a1f1f] text-sm font-bold font-[Montserrat] px-4 py-2.5 rounded-full hover:bg-[#b8ef00] transition-colors">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 flex-shrink-0"><circle cx="12" cy="12" r="3"/><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/></svg>
              Details
            </a>
            <a href="<?php echo esc_url( $msg_url ); ?>"
               class="flex-1 flex items-center justify-center gap-1.5 border border-[#C8FF00]/40 text-[#C8FF00] text-sm font-semibold font-[Montserrat] px-4 py-2.5 rounded-full hover:bg-[#C8FF00]/10 transition-colors">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 flex-shrink-0"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              Message
            </a>
          </div>
 
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- View All button -->
    <div class="text-center mt-10">
      <a href="<?php echo esc_url( home_url( '/tools' ) ); ?>"
         class="inline-flex items-center gap-2 border border-[#C8FF00] text-[#C8FF00] px-7 py-3 rounded-full text-sm sm:text-base font-semibold font-[Montserrat] hover:bg-[#C8FF00] hover:text-[#0a1f1f] transition-colors">
        View All Tools
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 sm:w-5">
          <line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/>
        </svg>
      </a>
    </div>

  </div>
</section>
<?php endif; ?>
<!-- ================= AVAILABLE TOOLS & MOULDS SECTION END ================= -->
       <!-- ================= Listing section send================= -->

  <!-- ================= faq section start ================= -->
<section class="w-full bg-[#142F32] py-16 px-6 md:px-12 lg:px-24 font-[Poppins]">
    
    <div class="reveal max-w-4xl mx-auto text-center pb-4">
        <p class="text-[#C8FF00] font-medium font-[Helvetica] text-[20px] tracking-widest   uppercase">FAQ</p>
        <h2 class="text-white text-2xl sm:text-3xl md:text-4xl font-medium leading-tight mt-2">
            Frequently Asked <span class="text-[#C8FF00]">Questions</span>
        </h2>
    </div>

    <div id="faq-list" class="reveal max-w-7xl mx-auto space-y-4 mb-4">
        
        <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
            <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
                <div class="flex items-center gap-4 md:gap-6">
                    <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
                        01
                    </span>
                    <h3 class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                      What is injection moulding and how does it work?</h3>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" 
                         alt="Toggle" 
                         class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
                         style="transform: rotate(0deg);"> 
                         </div>
            </button>

            <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
                <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
                    Injection moulding is a process where melted plastic is injected into a mould tool to create finished parts quickly and accurately.
                </p>
            </div>
        </div>
        
        </div>
        <div  class="reveal max-w-7xl mx-auto space-y-4 mb-4">
        
        <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
            <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
                <div class="flex items-center gap-4 md:gap-6">
                    <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
                        02
                    </span>
                    
                    <h3 class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                      What materials can be used in injection moulding?</h3>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" 
                         alt="Toggle" 
                         class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
                         style="transform: rotate(0deg);"> 
                         </div>
            </button>

            <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
                <p class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                    Common materials include PP, ABS, Nylon, PC, HDPE, LDPE, and many engineering plastics.
                </p>
            </div>
        </div>
        
        </div>
         <div  class="reveal max-w-7xl mx-auto space-y-4 mb-4">
        
        <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
            <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
                <div class="flex items-center gap-4 md:gap-6">
                    <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
                        03
                    </span>
                    <h3 class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                      How long does it take to get a quote?
                    </h3>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" 
                         alt="Toggle" 
                         class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
                         style="transform: rotate(0deg);"> 
                         </div>
            </button>

            <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
                <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
                Most quote requests are reviewed and matched with available manufacturers within 24-48 hours.
              </p>
            </div>
        </div>
        
        </div>
        <div  class="reveal max-w-7xl mx-auto space-y-4 mb-4">
        
        <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
            <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
                <div class="flex items-center gap-4 md:gap-6">
                    <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
                        04
                    </span>
                    <h3 class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                      Do I need to own a mould to use your service?
                    </h3>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" 
                         alt="Toggle" 
                         class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
                         style="transform: rotate(0deg);"> 
                         </div>
            </button>

            <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
                <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
                    No. You can list a project requiring tooling or use an existing mould tool you already own.
                </p>
            </div>
        </div>
        
        </div>
          <div class="reveal max-w-7xl mx-auto space-y-4 mb-4">
        
        <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
            <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
                <div class="flex items-center gap-4 md:gap-6">
                    <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
                        05
                    </span>
                    <h3 class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                      What volumes do you support?
                    </h3>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" 
                         alt="Toggle" 
                         class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
                         style="transform: rotate(0deg);"> 
                         </div>
            </button>

            <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
                <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
                    We support low-volume runs, repeat production, and high-volume manufacturing projects.
                </p>
            </div>
        </div>
        
        </div>
          <div  class="reveal max-w-7xl mx-auto space-y-4 mb-4">
        
        <div class="faq-item rounded-2xl bg-[#1a3d46] border border-white/5 overflow-hidden transition-all duration-300">
            <button class="w-full flex items-center justify-between px-8 py-3 text-left group">
                <div class="flex items-center gap-4 md:gap-6">
                    <span class="num-badge flex-shrink-0 w-10 h-10 rounded-full bg-[#2a505a] flex items-center justify-center text-white text-sm font-bold font-[Poppins] text-[16px] border border-white/10 transition-colors shadow-[inset_0_2px_4px_rgba(255,255,255,0.1)]">
                        06
                    </span>
                    <h3 class="text-white text-sm sm:text-base font-medium leading-snug md:text-[26px] font-[Helvetica]">
                      Are your manufacturing partners UK-based?
                    </h3>
                </div>
                
                <div class="flex-shrink-0 ml-4">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-down-right.png" 
                         alt="Toggle" 
                         class="faq-icon-img w-[20px] h-[20px] transition-transform duration-500 ease-in-out opacity-60 group-hover:opacity-100"
                         style="transform: rotate(0deg);"> 
                         </div>
            </button>

            <div class="answer hidden px-6 md:px-8 pb-4 md:pl-24 md:pr-24  transition-all duration-300">
                <p class="text-[#F0F1F5] text-sm md:text-[20px] font-medium font-[Montserrat] leading-relaxed max-w-6xl">
                    We work with UK-based and international moulding partners depending on your requirements.
                </p>
            </div>
        </div>
        
        </div>
</section>
 <!-- ================= FAQ section end ================= -->
   <!-- ================= Join Us Now section start ================= -->
<section class="w-full bg-[#f4f7f6] px-4 sm:px-6 lg:px-20 py-14 sm:py-16 lg:py-20">
  <div class="max-w-7xl mx-auto rounded-2xl bg-[#153F45] text-white p-6 sm:p-8 lg:p-12 flex flex-col items-center text-center shadow-xl">

    <!-- TAG -->
    <div class="relative mb-4">
      <div class="absolute -top-[1px] left-1/2 -translate-x-1/2 w-1/3 h-[2px] bg-gradient-to-r from-transparent via-[#d4ff00] to-transparent blur-[1px]"></div>

      <div class="flex items-center gap-2 px-4 py-1.5 border border-white/10 rounded-lg">
        <span class="w-2 h-2 bg-white rounded-full"></span>
        <span class="text-white text-sm sm:text-base font-light tracking-wide">
          Join Us Now
        </span>
      </div>
    </div>

    <!-- HEADING -->
    <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-[36px] font-semibold leading-tight mt-2 tracking-wide">
      Ready to Start Your Injection
      <br class="hidden sm:block" />
      <span class="text-[#C8FF00]">Moulding Project?</span>
    </h2>

    <!-- TEXT -->
    <p class="text-sm sm:text-base text-slate-200 leading-relaxed max-w-2xl mt-4 mb-6">
      Contact our expert team today to discuss your moulding requirements.
      We provide high-quality, reliable, and cost-effective plastic injection
      moulding solutions for different industries.
    </p>

    <!-- BUTTON -->
    <a href="/contact-us"
      class="inline-flex items-center gap-2 rounded-full border border-white px-5 sm:px-6 py-2.5 sm:py-3 text-sm sm:text-base font-medium transition active:scale-95 hover:bg-white hover:text-[#153F45]">

      Request a Quote

      <img src="<?php echo get_template_directory_uri(); ?>/assets/images/icons/arrow-up-right.png"
        class="w-4 sm:w-5" />
    </a>

  </div>
</section>
<!-- ================= Join Us Now section end ================= -->
 
 </main>
<?php get_footer(); ?>
